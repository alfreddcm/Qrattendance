# Student Password System Documentation

## Overview
This document describes the intelligent student password management system for QR Attendance. The system automatically generates default passwords based on student LRN (Learning Reference Number) and intelligently synchronizes them when the LRN changes.

## Key Features

### 1. Automatic Default Password on Import
When students are imported via CSV/Excel file:
- A default password is automatically generated: `Hash(LRN)`
- The password is marked as NOT CHANGED by the student (`password_changed = false`)
- Students can log in immediately with their LRN as the password

### 2. Smart Password Synchronization on LRN Update
When a student's LRN is updated:
- **If using default password**: The password is automatically updated to hash of the new LRN
  - The `password_changed` flag remains `false`
  - This prevents students from losing access due to LRN changes
- **If using custom password**: The password is preserved and NOT updated
  - The `password_changed` flag is checked - if it's `true`, password is not touched
  - This respects student autonomy and protects custom passwords

### 3. Password Change Tracking
- `password_changed` flag tracks whether student has customized their password
- Set to `true` when student manually changes password via the account settings
- Used to determine if LRN synchronization should occur on LRN update

## Database Schema

### students table fields:
```sql
- id (primary key)
- id_no (LRN - Learning Reference Number)
- password (bcrypt hashed)
- password_changed (boolean, default: false)
- ... other fields ...
```

## System Architecture

### Files Involved

#### 1. **app/Models/Student.php**
The core Student model with password management methods:

```php
// Check if student is using the default LRN-based password
$student->usesDefaultPassword() // boolean

// Set password back to LRN hash (typically after student resets to default)
$student->setDefaultPassword() // void

// Update password and mark as changed
$student->updatePassword($newPassword) // void
```

**Key Method: `usesDefaultPassword()`**
```php
public function usesDefaultPassword(): bool
{
    return !empty($this->password)
        && !empty($this->id_no)
        && Hash::check((string) $this->id_no, $this->password);
}
```
This method returns `true` if the current password hash matches the LRN hash.

#### 2. **app/Observers/StudentObserver.php**
Eloquent model observer that listens for student model events:

**`updating()` event** - Triggered when a student record is updated:
```php
if ($student->isDirty('id_no')) {
    $oldLrn = $student->getOriginal('id_no');
    $newLrn = $student->id_no;
    
    // Only update password if student is still using default LRN password
    if ($student->password && Hash::check($oldLrn, $student->password)) {
        $student->password = Hash::make($newLrn);
        // password_changed stays false
    }
    // Otherwise, preserve the custom password
}
```

**`created()` event** - Triggered when a student is created:
```php
if (!$student->password) {
    // Ensure password is set if missing
    $student->password = Hash::make($student->id_no);
    $student->password_changed = false;
    $student->save();
}
```

#### 3. **app/Imports/StudentsImport.php**
Handles bulk student imports from Excel/CSV files:
- Automatically sets `password = Hash::make($lrn)`
- Sets `password_changed = false` for all imported students

#### 4. **app/Http/Controllers/AuthController.php**
Handles student login and password verification:
- Uses `Hash::check($password, $student->password)` for password validation
- Checks if student is using default password: `$student->usesDefaultPassword()`
- Redirects to password change page if using default password

#### 5. **app/Http/Controllers/StudentDashboardController.php**
Handles password change requests:
- Validates new password (min 8 characters)
- Calls `$student->updatePassword($request->password)` to update password and mark as changed
- Logs password change event

#### 6. **database/migrations/2026_04_07_000000_add_password_changed_to_students_table.php**
Adds the `password_changed` column to students table (if not already present):
```php
$table->boolean('password_changed')->default(false)->after('password');
```

#### 7. **database/migrations/2026_05_18_000001_set_default_passwords_for_students.php**
One-time migration to fix all existing students with NULL passwords:
- Finds all students with `password = NULL`
- Sets password to `Hash::make($id_no)` for each
- Sets `password_changed = false`

## Workflow Scenarios

### Scenario 1: New Student Import
1. Admin imports CSV with students
2. StudentImport.php creates each student with `password = Hash(LRN)`
3. `password_changed = false` for all
4. Student logs in with: **username: LRN, password: LRN**
5. System detects `usesDefaultPassword() == true`
6. Redirects to "Change Password" page
7. Student sets custom password
8. `password_changed` flag is set to `true`

### Scenario 2: Admin Updates Student LRN (with Default Password)
1. Admin changes student's LRN from "500012200001" to "500012200002"
2. Student's password is still `Hash("500012200001")`
3. StudentObserver's `updating()` event fires
4. `Hash::check("500012200001", $student->password)` returns `true` (default password detected)
5. Password is automatically updated to `Hash("500012200002")`
6. Student can now log in with new LRN: "500012200002"

### Scenario 3: Admin Updates Student LRN (with Custom Password)
1. Student has changed password to custom value, `password_changed = true`
2. Admin changes student's LRN from "500012200001" to "500012200002"
3. Student's password is `Hash("myCustomPassword")`
4. StudentObserver's `updating()` event fires
5. `Hash::check("500012200001", $student->password)` returns `false` (custom password detected)
6. **Password is NOT updated** - remains `Hash("myCustomPassword")`
7. Student continues to log in with their custom password

### Scenario 4: Student Changes Password
1. Student accesses Account Settings page
2. Enters current password (or just new password if still using default)
3. Enters new password (min 8 characters)
4. Form submits to `StudentDashboardController@updatePassword`
5. Controller calls `$student->updatePassword($newPassword)`
6. Student password updated to `Hash($newPassword)`
7. `password_changed` set to `true`
8. Student logs out and back in with new password

## Execution Steps for Setup

### Step 1: Ensure Database Migrations are Run
```bash
php artisan migrate
```

This will:
- Create/verify `password_changed` column exists
- Fix all existing students with NULL passwords by setting them to `Hash(id_no)`

### Step 2: Verify Observer Registration
The StudentObserver is automatically registered in Student model's `booted()` method:
```php
protected static function booted()
{
    static::observe(StudentObserver::class);
}
```

### Step 3: Test the System
1. Create a new student via import
2. Test login with LRN as password
3. Change password to custom value
4. Update student LRN
5. Verify password syncs correctly based on `usesDefaultPassword()`

## Security Considerations

1. **Password Hashing**: All passwords are bcrypt hashed, never stored in plaintext
2. **Hash::check() validation**: Used for all password comparisons
3. **CSRF Protection**: All forms include CSRF tokens
4. **Session Management**: Sessions are database-backed and time-limited (480 minutes)
5. **Custom Password Preservation**: If student has set custom password, LRN changes won't compromise it

## Troubleshooting

### Issue: Student can't log in with LRN after import
**Solution**: Check if `password` field is NULL in database. Run:
```bash
php artisan migrate
```

### Issue: Password not syncing when LRN is updated
**Solution**: 
1. Verify `StudentObserver` is imported in Student.php
2. Verify `booted()` method is calling `static::observe(StudentObserver::class);`
3. Clear config cache: `php artisan config:clear`

### Issue: Custom password was overwritten when LRN changed
**Solution**: This shouldn't happen if observer is working. Check:
1. Is `password_changed` flag set to `true` when password was changed?
2. Is the observer's `updating()` event being triggered?
3. Add logging to StudentObserver to debug

## Related Commands

Reset a student's password to LRN hash:
```php
$student->setDefaultPassword();
```

Update student password manually:
```php
$student->updatePassword('newPassword');
```

Check if student is using default password:
```php
if ($student->usesDefaultPassword()) {
    // Prompt password change
}
```

## Future Enhancements

1. **Password Reset via Email**: Add email-based password reset functionality
2. **Password Expiration**: Implement password expiration policies
3. **Brute Force Protection**: Add login attempt limiting
4. **Audit Logging**: Log all password changes with timestamps and admin user
5. **2FA Support**: Add two-factor authentication option for students
