<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AttendanceAnalyticsController;
use App\Http\Controllers\StudentIdController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessageApiController;
use App\Http\Middleware\RoleMiddleware;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->role === 'teacher') {
            return redirect()->route('teacher.dashboard');
        } elseif ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
    }
    return redirect()->route('login');
})->name('home');

// Login routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware('guest');

// Teacher Routes - protected by teacher role
Route::middleware(['role:teacher'])->prefix('teacher')->group(function () {
    Route::get('/semesters', [SemesterController::class, 'index'])->name('teacher.semesters');
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('teacher.dashboard');
    
    // Teachers can only edit semesters, not create them
    Route::get('/semester/{semester}/data', [SemesterController::class, 'show'])->name('teacher.semester.data');
    Route::get('/semester/{semester}/edit', [SemesterController::class, 'edit'])->name('teacher.semester.edit');
    Route::put('/semester/{semester}', [SemesterController::class, 'update'])->name('teacher.semester.update');
    Route::post('/semester/{semester}/toggle-status', [SemesterController::class, 'toggleStatus'])->name('teacher.semester.status.update');
    Route::get('/semester/active', [SemesterController::class, 'getActiveSemester'])->name('teacher.semester.active');

    Route::get('/students', [StudentManagementController::class, 'index'])->name('teacher.students');
    Route::post('/students/add', [StudentManagementController::class, 'addStudent'])->name('teacher.students.add');
    Route::get('/students/{id}/edit', [StudentManagementController::class, 'edit'])->name('teacher.students.edit');
    Route::put('/students/{id}', [StudentManagementController::class, 'update'])->name('teacher.students.update');
    Route::delete('/students/{id}', [StudentManagementController::class, 'destroy'])->name('teacher.students.destroy');
    Route::delete('/students/bulk-delete', [StudentManagementController::class, 'bulkDelete'])->name('teacher.students.bulkDelete');
    Route::get('/students/export', [StudentManagementController::class, 'export'])->name('teacher.students.export');
    Route::get('/students/download-template', [StudentManagementController::class, 'downloadTemplate'])->name('teacher.students.downloadTemplate');

    Route::post('/students/generate-qrs', [StudentManagementController::class, 'generateQrs'])->name('teacher.students.generateQrs');
    Route::get('/students/print-qrs', [StudentManagementController::class, 'printQrs'])->name('teacher.students.printQrs');
    Route::get('/students/download-qrs', [StudentManagementController::class, 'downloadQrs'])->name('teacher.students.downloadQrs');
    Route::post('/students/{id}/generate-qr', [StudentManagementController::class, 'generateQr'])->name('teacher.students.generateQr');

    Route::get('/message', [TeacherController::class, 'message'])->name('teacher.message');
    Route::get('/attendance', [AttendanceAnalyticsController::class, 'attendanceToday'])->name('teacher.attendance');
    
    Route::get('/analytics/statistics', [AttendanceAnalyticsController::class, 'statistics'])->name('teacher.analytics.statistics');
    Route::get('/analytics/daily-trends', [AttendanceAnalyticsController::class, 'dailyTrends'])->name('teacher.analytics.daily');
    Route::get('/analytics/patterns', [AttendanceAnalyticsController::class, 'timePatterns'])->name('teacher.analytics.patterns');
    Route::get('/analytics/time-distribution', [AttendanceAnalyticsController::class, 'getTimeDistribution'])->name('teacher.analytics.time');
    Route::get('/analytics/student-performance', [AttendanceAnalyticsController::class, 'studentForecast'])->name('teacher.analytics.performance');
    
    Route::get('/report', [ReportController::class, 'index'])->name('teacher.report');
    Route::post('/attendance/export/csv', [ReportController::class, 'exportCsv'])->name('teacher.attendance.export.csv');
    Route::post('/students/import-excel', [TeacherController::class, 'importExcel'])->name('teacher.students.importExcel');
    Route::get('/account', [TeacherController::class, 'account'])->name('teacher.account');
    Route::put('/account', [TeacherController::class, 'update'])->name('teacher.account.update');
    Route::put('/account/password', [TeacherController::class, 'updatePassword'])->name('teacher.account.password');
    
    Route::post('/qr-verify', [AttendanceController::class, 'verifyQrAndRecordAttendance'])->name('teacher.qr.verify');
    
    // SMS/Message Routes
    Route::post('/send-sms', [MessageApiController::class, 'sendSms'])->name('teacher.send.sms');
    Route::get('/outbound-messages', [MessageApiController::class, 'getOutboundMessages'])->name('teacher.outbound.messages');
    Route::get('/message-status/{id}', [MessageApiController::class, 'getMessageStatus'])->name('teacher.message.status');
    Route::get('/test-sms-gateway', [MessageApiController::class, 'testGateway'])->name('teacher.test.gateway');
    Route::get('/get-students', [StudentManagementController::class, 'getStudentsForApi'])->name('teacher.get.students');
    
    // Attendance Session Routes
    Route::post('/attendance-session/create', [App\Http\Controllers\AttendanceSessionController::class, 'createSession'])->name('teacher.attendance.session.create');
    Route::get('/attendance-session/active', [App\Http\Controllers\AttendanceSessionController::class, 'getActiveSessions'])->name('teacher.attendance.session.active');
    Route::get('/attendance-session/today', [App\Http\Controllers\AttendanceSessionController::class, 'getTodaySession'])->name('teacher.attendance.session.today');
    Route::post('/attendance-session/{id}/close', [App\Http\Controllers\AttendanceSessionController::class, 'closeSession'])->name('teacher.attendance.session.close');

});

    // Admin Routes
Route::middleware(['role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // School Management
    Route::get('/manage-schools', [AdminController::class, 'manageSchools'])->name('admin.manage-schools');
    Route::get('/add-school', [AdminController::class, 'addSchoolForm'])->name('admin.add-school');
    Route::post('/store-school', [AdminController::class, 'storeSchool'])->name('admin.store-school');
    Route::get('/edit-school/{id}', [AdminController::class, 'editSchoolForm'])->name('admin.edit-school');
    Route::put('/update-school/{id}', [AdminController::class, 'updateSchool'])->name('admin.update-school');
    Route::delete('/delete-school/{id}', [AdminController::class, 'deleteSchool'])->name('admin.delete-school');
    
    // Teacher Management
    Route::get('/manage-teachers', [AdminController::class, 'manageTeachers'])->name('admin.manage-teachers');
    Route::post('/store-teacher', [AdminController::class, 'storeTeacher'])->name('admin.store-teacher');
    Route::put('/teachers/{id}', [AdminController::class, 'updateTeacher'])->name('admin.update-teacher');
    Route::delete('/teachers/{id}', [AdminController::class, 'deleteTeacher'])->name('admin.delete-teacher');
    
    // Semester Management (Admin creates, teachers edit)
    Route::get('/manage-semesters', [SemesterController::class, 'index'])->name('admin.manage-semesters');
    Route::get('/semesters/create', [SemesterController::class, 'create'])->name('admin.semester.create');
    Route::post('/semesters/store', [SemesterController::class, 'store'])->name('admin.semester.store');
    Route::get('/semesters/{semester}/edit', [SemesterController::class, 'edit'])->name('admin.semester.edit');
    Route::put('/semesters/{semester}', [SemesterController::class, 'update'])->name('admin.semester.update');
    Route::delete('/semesters/{semester}', [SemesterController::class, 'destroy'])->name('admin.semester.delete');
    Route::post('/semesters/{semester}/toggle-status', [SemesterController::class, 'toggleStatus'])->name('admin.semester.toggle-status');
    Route::get('/semester/active', [SemesterController::class, 'getActiveSemester'])->name('admin.semester.active');
    
    // Student Management
    Route::get('/manage-students', [AdminController::class, 'manageStudents'])->name('admin.manage-students');
    Route::post('/students/store', [AdminController::class, 'storeStudent'])->name('admin.students.store');
    Route::put('/students/{id}', [AdminController::class, 'updateStudent'])->name('admin.students.update');
    Route::delete('/students/{id}', [AdminController::class, 'deleteStudent'])->name('admin.students.destroy');
    Route::delete('/students/bulk-delete', [AdminController::class, 'bulkDeleteStudents'])->name('admin.students.bulkDelete');
    Route::post('/students/bulk-export', [AdminController::class, 'bulkExportStudents'])->name('admin.students.bulkExport');
    Route::post('/students/generate-qrs', [AdminController::class, 'generateQrs'])->name('admin.students.generateQrs');
    Route::get('/students/print-qrs', [AdminController::class, 'printQrs'])->name('admin.students.printQrs');
    Route::get('/students/download-qrs', [AdminController::class, 'downloadQrs'])->name('admin.students.downloadQrs');
    Route::post('/students/{id}/generate-qr', [AdminController::class, 'generateQr'])->name('admin.students.generateQr');
    Route::get('/students/export', [AdminController::class, 'exportStudents'])->name('admin.students.export');
    
    // Admin Import Routes
    Route::get('/students/download-template', [AdminController::class, 'downloadTemplate'])->name('admin.students.downloadTemplate');
    Route::get('/students/download-sample-data', [AdminController::class, 'downloadSampleData'])->name('admin.students.downloadSampleData');
    Route::post('/students/import', [AdminController::class, 'importStudents'])->name('admin.students.import');
    Route::get('/students/import-guide', [AdminController::class, 'importGuide'])->name('admin.students.importGuide');
    
    Route::get('/attendance-reports', [AdminController::class, 'attendanceReports'])->name('admin.attendance-reports');
});

Route::middleware(['role:teacher,admin'])->group(function () {
    Route::get('/student-id/print/{id}', [StudentIdController::class, 'printSingle'])->name('student.id.print');
    Route::get('/student-ids/print-all', [StudentIdController::class, 'printAll'])->name('student.ids.print.all');
    Route::get('/student-ids/print-by-teacher/{teacherId}', [StudentIdController::class, 'printByTeacher'])->name('student.ids.print.by.teacher');
    Route::get('/student-ids/print-my-students', [StudentIdController::class, 'printMyStudents'])->name('student.ids.print.my.students');
    
    // Import routes
    Route::post('/import/upload', [ImportController::class, 'preview'])->name('import.upload');
    Route::post('/import/import', [ImportController::class, 'import'])->name('import.import');
});

 Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Public Attendance Routes (no auth required)
Route::get('/attendance/{token}', [App\Http\Controllers\AttendanceSessionController::class, 'publicAttendance'])->name('attendance.public');
Route::post('/attendance/{token}/qr-verify', [App\Http\Controllers\AttendanceSessionController::class, 'publicQrVerify'])->name('attendance.public.verify');
Route::get('/attendance/{token}/status', [App\Http\Controllers\AttendanceSessionController::class, 'checkSessionStatus'])->name('attendance.public.status');

// API Routes
Route::get('/api/semester/time-sessions', [App\Http\Controllers\AttendanceSessionController::class, 'getTimeSessions']);

// Attendance Forecasting
Route::get('/teacher/attendance-forecast', [App\Http\Controllers\AttendanceForecastController::class, 'index'])->name('teacher.attendance.forecast');

