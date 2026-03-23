# Student Authentication & Dashboard - Testing Checklist

## Pre-Testing Setup

### 1. Database Migration
- [ ] Run migration: `php artisan migrate`
  - Verify `password` and `remember_token` columns added to `students` table
  - Check column types and nullability

### 2. Set Student Passwords
```bash
# Set password for all students in a school (use defaults: student ID number)
php artisan students:set-password --school-id=1

# Or set password for specific student
php artisan students:set-password --id_no=2024001 --password=TestPassword123

# Or set for all students
php artisan students:set-password
```

---

## Authentication Testing

### Test 1: Student Login with ID Number
**Steps:**
1. Navigate to login page at `/login`
2. Enter Student ID (id_no) in username field
3. Enter correct password
4. Click Sign In

**Expected Results:**
- [ ] Login succeeds
- [ ] Redirected to `/student/dashboard`
- [ ] User session established
- [ ] Correct student data displayed

### Test 2: Student Login with Student Code (LRN)
**Steps:**
1. Navigate to login page
2. Enter Student Code (stud_code) in username field
3. Enter correct password
4. Click Sign In

**Expected Results:**
- [ ] Login succeeds
- [ ] Redirected to `/student/dashboard`
- [ ] Same student profile displayed

### Test 3: Teacher Login (Verify Not Broken)
**Steps:**
1. Navigate to login page
2. Enter teacher username
3. Enter correct teacher password
4. Click Sign In

**Expected Results:**
- [ ] Login succeeds
- [ ] Redirected to `/teacher/dashboard`
- [ ] Teacher functionality unchanged

### Test 4: Admin Login (Verify Not Broken)
**Steps:**
1. Navigate to login page
2. Enter admin username
3. Enter correct admin password
4. Click Sign In

**Expected Results:**
- [ ] Login succeeds
- [ ] Redirected to `/admin/dashboard`
- [ ] Admin functionality unchanged

### Test 5: Invalid Student Credentials
**Steps:**
1. Navigate to login page
2. Enter valid student ID
3. Enter wrong password
4. Click Sign In

**Expected Results:**
- [ ] Login fails
- [ ] Error message: "Invalid login credentials."
- [ ] Remains on login page
- [ ] Password field cleared

### Test 6: Nonexistent Student ID
**Steps:**
1. Navigate to login page
2. Enter nonexistent student ID
3. Enter any password
4. Click Sign In

**Expected Results:**
- [ ] Login fails
- [ ] Error message: "Invalid login credentials."
- [ ] Remains on login page

---

## Student Dashboard Testing

### Test 7: Dashboard Display - Logged In
**Steps:**
1. Login as student
2. Verify dashboard loads

**Expected Results:**
- [ ] School logo displays (if available)
- [ ] School name displays correctly
- [ ] Student name displays
- [ ] Student ID (id_no) displays
- [ ] LRN/Student Code (stud_code) displays
- [ ] Section name displays
- [ ] Teacher/Adviser name displays from section.teacher

### Test 8: Today's Attendance Display
**Steps:**
1. Logged in as student on dashboard
2. Check "Today's Attendance" section

**Expected Results:**
- [ ] If attendance exists for today:
  - Morning In time displays (or "No Entry")
  - Morning Out time displays (or "-")
  - PM In time displays (or "-")
  - PM Out time displays (or "-")
  - Status badge shows correctly (Full Day/Partial/Morning Only/Afternoon Only/No Attendance)
- [ ] If no attendance exists:
  - Message "No attendance record for today yet" displays

### Test 9: Attendance History Route
**Steps:**
1. From dashboard, click "View History" button
2. Verify page loads

**Expected Results:**
- [ ] Page title: "Attendance History"
- [ ] Filter form visible (Start Date, End Date, Filter button)
- [ ] Table displays all student's attendance records
- [ ] Records sorted by date (newest first)

### Test 10: Attendance History Filtering
**Steps:**
1. On attendance history page
2. Enter start date: 2025-01-01
3. Enter end date: 2025-01-31
4. Click Filter

**Expected Results:**
- [ ] Only records within date range display
- [ ] Table shows correct number of records
- [ ] All fields (times, status) display correctly

### Test 11: Attendance History Pagination
**Steps:**
1. View attendance history
2. If more than 15 records exist, verify pagination

**Expected Results:**
- [ ] Records shown in sets of 15
- [ ] Pagination controls visible
- [ ] Can navigate between pages

---

## Student Account Testing

### Test 12: Account Page Display
**Steps:**
1. From dashboard, click "Edit Account" button
2. Verify page loads

**Expected Results:**
- [ ] Page title: "My Account"
- [ ] Profile Information section shows:
  - Full name (disabled)
  - Student ID (disabled)
  - LRN/Student Code (disabled)
  - Gender (disabled)
  - Age (disabled)
  - Section (disabled)
  - Contact Number (disabled)
  - Address (disabled)
- [ ] Emergency Contact section displays
- [ ] Change Password form visible

### Test 13: Change Password - Valid
**Steps:**
1. On account page
2. Enter current password (correct)
3. Enter new password: NewPass123
4. Confirm password: NewPass123
5. Click "Update Password"

**Expected Results:**
- [ ] Success message displays
- [ ] Password updated in database
- [ ] Can login with new password

### Test 14: Change Password - Incorrect Current Password
**Steps:**
1. On account page
2. Enter current password (wrong)
3. Enter new password: NewPass123
4. Confirm password: NewPass123
5. Click "Update Password"

**Expected Results:**
- [ ] Error message: "Current password is incorrect."
- [ ] Password not changed
- [ ] Can still login with old password

### Test 15: Change Password - Mismatch
**Steps:**
1. On account page
2. Enter current password (correct)
3. Enter new password: NewPass123
4. Confirm password: DifferentPass456
5. Click "Update Password"

**Expected Results:**
- [ ] Validation error displays
- [ ] Password not changed

### Test 16: Change Password - Too Short
**Steps:**
1. On account page
2. Enter current password (correct)
3. Enter new password: Pass (less than 8 chars)
4. Confirm password: Pass
5. Click "Update Password"

**Expected Results:**
- [ ] Validation error: "Password must be at least 8 characters"
- [ ] Password not changed

---

## Session & Logout Testing

### Test 17: Logout
**Steps:**
1. Login as student
2. From any student page, click Sign Out button (or use form)

**Expected Results:**
- [ ] Session ends
- [ ] Redirected to login page
- [ ] Success message: "You have been logged out successfully."

### Test 18: Access Protected Route Without Login
**Steps:**
1. Logout (or don't login)
2. Try to access `/student/dashboard`

**Expected Results:**
- [ ] Redirected to login page

### Test 19: Session Persistence (Remember Me Optional)
**Steps:**
1. Login as student
2. Close browser
3. Reopen browser on same domain

**Expected Results:**
- [ ] Session maintains (redirects to dashboard) OR
- [ ] Session expired (redirects to login) - both acceptable

---

## Role-Based Access Control Testing

### Test 20: Student Cannot Access Teacher Routes
**Steps:**
1. Login as student
2. Try to access `/teacher/dashboard`

**Expected Results:**
- [ ] Access denied
- [ ] Redirected to `/student/dashboard`
- [ ] Error message about insufficient permissions

### Test 21: Student Cannot Access Admin Routes
**Steps:**
1. Login as student
2. Try to access `/admin/dashboard`

**Expected Results:**
- [ ] Access denied
- [ ] Redirected to `/student/dashboard`
- [ ] Error message about insufficient permissions

### Test 22: Student Can Only Access Own Attendance
**Steps:**
1. Login as Student A
2. View own attendance via `/student/attendance`
3. Verify only Student A's records show

**Expected Results:**
- [ ] Only Student A's attendance records visible
- [ ] Cannot see other students' data through URL manipulation

---

## Data Integrity Testing

### Test 23: Attendance Data Accuracy
**Steps:**
1. Login as student
2. Check today's attendance on dashboard
3. Verify in database: SELECT * FROM attendances WHERE student_id=X AND date=TODAY

**Expected Results:**
- [ ] Times match exactly
- [ ] Status calculated correctly
- [ ] All four time fields (if applicable) display correctly

### Test 24: Student Profile Data Accuracy
**Steps:**
1. Login as student
2. Check all displayed fields on dashboard
3. Compare with database values

**Expected Results:**
- [ ] All student details match database
- [ ] School info matches school_id
- [ ] Section info matches section_id
- [ ] Teacher/adviser matches section.teacher_id

---

## Edge Cases & Error Handling

### Test 25: Student with No Section
**Steps:**
1. Create a student with section_id = NULL
2. Login as that student
3. View dashboard

**Expected Results:**
- [ ] Dashboard loads without errors
- [ ] Section field shows "N/A"
- [ ] Adviser field shows "N/A"
- [ ] No null/error messages in UI

### Test 26: Student with No School
**Steps:**
1. Student with school_id = NULL
2. Login and view dashboard

**Expected Results:**
- [ ] School name shows "School" (default)
- [ ] No database errors
- [ ] Page loads without errors

### Test 27: Multiple Students with Same ID Number (Validation)
**Steps:**
1. Attempt to create two students with same id_no
2. Try to create via admin panel or direct SQL

**Expected Results:**
- [ ] Database constraint prevents duplicate
- [ ] Error message displays to admin

### Test 28: Student Login After Password Reset
**Steps:**
1. Admin runs: `php artisan students:set-password --id_no=2024001 --password=ResetPass123`
2. Student logs in with new password

**Expected Results:**
- [ ] Login successful with new password
- [ ] Old password no longer works

---

## Performance & Load Testing

### Test 29: Dashboard Load Time
**Steps:**
1. Login as student
2. Time dashboard page load

**Expected Results:**
- [ ] Page loads in < 2 seconds
- [ ] All database queries optimized
- [ ] No N+1 queries

### Test 30: Attendance History Load with Large Dataset
**Steps:**
1. Student with 500+ attendance records
2. Load attendance history page

**Expected Results:**
- [ ] Page loads within reasonable time
- [ ] Pagination handles load
- [ ] No timeout errors

---

## Cross-Browser Testing (Optional)

- [ ] Chrome/Edge - Latest version
- [ ] Firefox - Latest version
- [ ] Safari - Latest version
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

**Expected Results for All:**
- [ ] Layout responsive
- [ ] Forms submit correctly
- [ ] Times format correctly
- [ ] No console errors

---

## Security Testing

### Test 31: Password Hashing in Database
**Steps:**
1. Set student password: SimplePass123
2. Check students table in database

**Expected Results:**
- [ ] Password is HASHED, not visible plaintext
- [ ] Hash starts with `$2y$` (bcrypt)

### Test 32: Session Hijacking Prevention
**Steps:**
1. Login as student (copy session ID)
2. Attempt to use session from different IP/browser

**Expected Results:**
- [ ] Session remains valid (Laravel default)
- [ ] Or session invalidated (if configured)

### Test 33: CSRF Protection
**Steps:**
1. Change password form
2. Intercept form submission
3. Verify CSRF token in request

**Expected Results:**
- [ ] CSRF token present in form
- [ ] POST requests include valid token
- [ ] Requests without token rejected

### Test 34: SQL Injection Prevention
**Steps:**
1. Login with username: `' OR '1'='1`
2. Enter any password

**Expected Results:**
- [ ] Login fails (not injectable)
- [ ] No error messages revealing database structure

---

## Admin/Teacher Functionality Not Broken

### Test 35: Teacher Student Management Still Works
**Steps:**
1. Login as teacher
2. Add new student via teacher panel
3. Edit existing student
4. Generate QR codes

**Expected Results:**
- [ ] All operations work as before
- [ ] New students can login with generated passwords

### Test 36: Admin Reports Still Work
**Steps:**
1. Login as admin
2. Generate attendance reports
3. View student management

**Expected Results:**
- [ ] All charts and reports display
- [ ] Student data accurate
- [ ] No new errors

---

## Final Verification Checklist

- [ ] All 36 tests pass
- [ ] No database errors in logs
- [ ] No console errors on any page
- [ ] Login page updated with student login option (optional UI enhancement)
- [ ] Documentation updated
- [ ] Deployment ready

---

## Troubleshooting Guide

### Issue: "Student model not found" error
**Solution:**
1. Check StudentDashboardController import
2. Run: `php artisan config:cache`
3. Verify route registered correctly

### Issue: "Column 'password' doesn't exist"
**Solution:**
1. Run migration: `php artisan migrate`
2. Check migration file in database/migrations
3. Verify table name in Student model

### Issue: Student login gives "Invalid credentials" even with correct password
**Solution:**
1. Check password is hashed: `php artisan students:set-password --id_no=XXXX`
2. Verify student exists: `Student::where('id_no', 'XXXX')->first()`
3. Test hash: `Hash::check('password', $student->password)`

### Issue: Student can access teacher routes
**Solution:**
1. Verify RoleMiddleware registers 'student' case
2. Check route middleware: `middleware(['role:student'])`
3. Verify student->role returns 'student'

### Issue: Password change fails silently
**Solution:**
1. Check form CSRF token present
2. Verify current password is correct
3. Check password_confirmation field matches
4. Verify password length >= 8 characters

