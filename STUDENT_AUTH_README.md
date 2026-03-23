# Student Authentication & Dashboard Implementation

## Overview

This implementation extends the QR Attendance system to support **student login and attendance dashboard**. Students can now:

- Login using their **Student ID (id_no)** or **Student Code (stud_code)**
- View their **attendance dashboard** with today's in/out times
- Check **attendance history** with date range filtering
- Manage their **account** and change password
- Access their **own attendance data only** (role-based security)

## Architecture

### Authentication Flow
```
Login Request
    ↓
UserModel attempt (username/password)
    ↓ (if fails)
StudentModel attempt (id_no or stud_code + password)
    ↓ (success)
Auth::login($student)
    ↓
Redirect by role (teacher/admin/student)
```

### Key Design Decisions

1. **Reuses Existing Architecture**: Single 'web' guard, uses role-based middleware
2. **Student Model is Authenticatable**: No new guard needed
3. **Backward Compatible**: All teacher/admin functionality unchanged
4. **Role-Based Access**: Student routes protected with `middleware(['role:student'])`

## Database Schema

### New Migration
- **File**: `database/migrations/2026_03_23_000000_add_password_to_students_table.php`
- **Adds**:
  - `password` (string, nullable)
  - `remember_token` (string, nullable)

### Database Structure
```
students table
├── id
├── id_no (unique, login identifier)
├── stud_code (unique, alternative login)
├── name
├── password (hashed)
├── remember_token
├── section_id (FK → sections)
├── school_id (FK → schools)
├── school_year_id
└── ... (other existing fields)
```

## Installation & Setup

### Step 1: Run Migration
```bash
php artisan migrate
```

### Step 2: Set Student Passwords

#### Option A: Set password for specific student
```bash
php artisan students:set-password --id_no=2024001 --password=MyPassword123
```

#### Option B: Set passwords for all students in school (use ID as password)
```bash
php artisan students:set-password --school-id=1
```

#### Option C: Set passwords for all students (use ID as password)
```bash
php artisan students:set-password
```

**Note**: Default password is the student's `id_no` if not specified. Students should change this after first login.

### Step 3: Clear Cache (Important!)
```bash
php artisan config:cache
php artisan route:cache
```

## File Structure

### New Files Created (6 total)

```
app/
├── Console/
│   └── Commands/
│       └── SetStudentPasswords.php          # Artisan command for initial passwords
├── Http/
│   └── Controllers/
│       └── StudentDashboardController.php   # Student dashboard logic
│
database/
└── migrations/
    └── 2026_03_23_000000_add_password_to_students_table.php
│
resources/
└── views/
    └── student/
        ├── dashboard.blade.php   # Main dashboard view
        ├── attendance.blade.php  # Attendance history view
        └── account.blade.php     # Account management view
```

### Modified Files (4 total)

1. **app/Models/Student.php**
   - `Authenticatable` trait added
   - `Notifiable` trait added
   - `password` and `remember_token` added to fillable/hidden
   - `getRoleAttribute()` added (returns 'student')

2. **app/Http/Controllers/AuthController.php**
   - `login()` method extended to check Student model
   - Attempts User authentication first, then Student
   - Logs student login attempts

3. **app/Http/Middleware/RoleMiddleware.php**
   - Added 'student' case in `redirectToUserDashboard()`
   - Routes students to `/student/dashboard`

4. **routes/web.php**
   - Added `StudentDashboardController` import
   - Added student route group with middleware
   - Updated home route to include student redirect

## API Routes

### Student Routes (Protected with `role:student` middleware)

```
GET  /student/dashboard          → StudentDashboardController::dashboard
GET  /student/attendance         → StudentDashboardController::attendance
GET  /student/account            → StudentDashboardController::account
PUT  /student/account/password   → StudentDashboardController::updatePassword
POST /logout                      → (shared with all users)
```

### Authentication Routes (Public)
```
GET  /login          → Show login form
POST /login          → AuthController::login
POST /logout         → AuthController::logout
```

## Features

### Dashboard
- School logo and name display
- Student profile card (ID, LRN/Code, Section, Adviser)
- Today's attendance summary (in/out times for AM/PM)
- Attendance status badge
- Quick links to history, account, and logout

### Attendance History
- Filterable by date range
- Paginated (15 records per page)
- Shows all attendance records with times and status
- Color-coded status indicators

### Account Management
- View profile information (read-only)
- Change password with validation
- Emergency contact information display
- Account actions (dashboard, logout)

## Security Features

✅ **Password Security**
- Passwords hashed with bcrypt (via `password` cast)
- Hash verification for login
- Minimum 8 character requirement for new passwords

✅ **Session Security**
- CSRF token protection on all forms
- Session regeneration on login
- Session invalidation on logout

✅ **Access Control**
- Role-based middleware prevents cross-role access
- Students can only view their own data
- No access to teacher/admin functionalities

✅ **Data Protection**
- Passwords hidden from serialization
- Attendance data sanitized in views
- SQL injection prevention (Eloquent models)

## Testing

See `TESTING_CHECKLIST.md` for comprehensive testing guide including:
- Authentication tests (all user types)
- Dashboard display tests
- Account management tests
- Session/logout tests
- RBAC tests
- Security tests
- Performance tests

**Quick Test:**
```bash
# Login as student
- ID: 2024001 (or any valid student id_no)
- Password: 2024001 (default)
- Navigate to: http://localhost/login
```

## Troubleshooting

### Issue: "Model 'Student' not found"
```bash
php artisan config:cache
php artisan route:cache
```

### Issue: "Column 'password' doesn't exist"
```bash
php artisan migrate
php artisan migrate:status
```

### Issue: Student login fails but password is correct
```bash
# Verify password is hashed:
php artisan students:set-password --id_no=2024001

# Test in tinker:
php artisan tinker
> $s = Student::find(1); Hash::check('password', $s->password)
```

### Issue: Student can access teacher routes
```bash
# Verify role middleware:
php artisan route:list | grep student
# Check RoleMiddleware includes 'student' case
```

## Customization

### Change Default Password Scheme
**File**: `app/Console/Commands/SetStudentPasswords.php`

Currently uses `$idNo` as default. To change:
```php
$newPassword = $password ?? $idNo; // Change $idNo to something else
```

### Modify Dashboard Display
**File**: `resources/views/student/dashboard.blade.php`

Add more attendance metrics or school info as needed.

### Extend Attendance History
**File**: `app/Http/Controllers/StudentDashboardController.php`

Add more scopes, filters, or export functionality:
```php
public function attendance(Request $request)
{
    // Add custom filters here
}
```

## Performance Considerations

### Database Queries Optimized
- Dashboard: 4 queries (student + school + section + today's attendance)
- Attendance History: Uses pagination to limit memory usage
- Uses Eloquent relationships to prevent N+1 queries

### Recommendations for Scale
- Add index on `students(id_no)` and `students(stud_code)`
- Cache school logo URLs if serving many students
- Use database query caching for attendance summaries
- Consider pagination limit if ≥1000 students per section

## Browser Compatibility

✅ Chrome 90+
✅ Firefox 88+
✅ Safari 14+
✅ Edge 90+
✅ Mobile Chrome/Safari

## Logging

Student login attempts and events are logged at:
- `storage/logs/laravel.log`

```
[2026-03-23 10:15:30] local.INFO: Student logged in successfully {
  "student_id": 150,
  "id_no": "2024001",
  "ip": "192.168.1.100"
}
```

Failed attempts also logged for security monitoring.

## Future Enhancements

1. **Email Notifications**
   - Email attendance to parents
   - Manual notification system

2. **Mobile App API**
   - JSON responses for mobile app
   - Real-time attendance tracking

3. **Attendance Analytics**
   - Student dashboard with charts
   - Monthly attendance trends

4. **QR Code Scanner**
   - Allow students to scan to confirm attendance

5. **Multi-Factor Authentication**
   - SMS/Email verification on login

## Support & Maintenance

### Regular Tasks
- Monitor login failures for security issues
- Backup student password data
- Review access logs monthly
- Test recovery procedures quarterly

### When Issues Occur
1. Check `TESTING_CHECKLIST.md` troubleshooting section
2. Review logs in `storage/logs/laravel.log`
3. Verify migrations: `php artisan migrate:status`
4. Clear caches: `php artisan cache:clear`

## Rollback

If needed to revert this implementation:

```bash
# Rollback migration only
php artisan migrate:rollback --step=1

# Or manually drop columns:
Schema::table('students', function (Blueprint $table) {
    $table->dropColumn(['password', 'remember_token']);
});
```

Then remove the created files and modifications.

---

**Version**: 1.0
**Last Updated**: 2026-03-23
**Status**: Production Ready
