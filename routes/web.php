<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\StudentManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AttendanceAnalyticsController;
use App\Http\Controllers\StudentIdController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessageApiController;
use App\Http\Controllers\AttendanceCodeController;
use App\Http\Controllers\PublicAttendanceController;
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

 Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware(\Illuminate\Auth\Middleware\RedirectIfAuthenticated::class);
Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware(\Illuminate\Auth\Middleware\RedirectIfAuthenticated::class);

 Route::middleware(['role:teacher'])->prefix('teacher')->group(function () {
    Route::get('/school-years', [SchoolYearController::class, 'index'])->name('teacher.school-years');
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('teacher.dashboard');
    
    Route::get('/school-year/{schoolYear}/data', [SchoolYearController::class, 'show'])->name('teacher.school-year.data');
    Route::get('/school-year/{schoolYear}/edit', [SchoolYearController::class, 'edit'])->name('teacher.school-year.edit');
    Route::put('/school-year/{schoolYear}', [SchoolYearController::class, 'update'])->name('teacher.school-year.update');
    Route::post('/school-year/{schoolYear}/toggle-status', [SchoolYearController::class, 'toggleStatus'])->name('teacher.school-year.status.update');
    Route::get('/school-year/active', [SchoolYearController::class, 'getActiveSchoolYear'])->name('teacher.school-year.active');

     Route::post('/sections/store', [SectionController::class, 'store'])->name('teacher.section.store');
    Route::get('/sections/{section}/edit', [SectionController::class, 'edit'])->name('teacher.section.edit');
    Route::put('/sections/{section}', [SectionController::class, 'update'])->name('teacher.section.update');
    Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('teacher.section.destroy');

    Route::get('/students', [StudentManagementController::class, 'index'])->name('teacher.students');
    Route::post('/students/add', [StudentManagementController::class, 'addStudent'])->name('teacher.students.add');
    Route::get('/students/{id}/edit', [StudentManagementController::class, 'edit'])->name('teacher.students.edit');
    Route::put('/students/{id}', [StudentManagementController::class, 'update'])->name('teacher.students.update');
    Route::post('/students/{id}/quick-update', [StudentManagementController::class, 'quickUpdate'])->name('teacher.students.quick-update');
    Route::get('/students/sections', [StudentManagementController::class, 'getSections'])->name('teacher.students.sections');
    Route::delete('/students/{id}', [StudentManagementController::class, 'destroy'])->name('teacher.students.destroy');
    Route::delete('/students/bulk-delete', [StudentManagementController::class, 'bulkDelete'])->name('teacher.students.bulkDelete');
    Route::get('/students/export', [StudentManagementController::class, 'export'])->name('teacher.students.export');
    Route::get('/students/download-template', [StudentManagementController::class, 'downloadTemplate'])->name('teacher.students.downloadTemplate');
    Route::get('/students/import', [ImportController::class, 'showUploadForm'])->name('teacher.students.import');

    Route::post('/students/generate-qrs', [StudentManagementController::class, 'generateQrs'])->name('teacher.students.generateQrs');
    Route::get('/students/print-qrs', [StudentManagementController::class, 'printQrs'])->name('teacher.students.printQrs');
    Route::get('/students/download-qrs', [StudentManagementController::class, 'downloadQrs'])->name('teacher.students.downloadQrs');
    Route::post('/students/{id}/generate-qr', [StudentManagementController::class, 'generateQr'])->name('teacher.students.generateQr');

    Route::get('/message', [TeacherController::class, 'message'])->name('teacher.message');
    Route::get('/attendance', [AttendanceAnalyticsController::class, 'attendanceToday'])->name('teacher.attendance');
    Route::get('/attendance/live', [App\Http\Controllers\AttendanceSessionController::class, 'teacherAttendanceLive'])->name('teacher.attendance.live');
    Route::post('/attendance/live/qr-verify', [App\Http\Controllers\AttendanceSessionController::class, 'teacherQrVerify'])->name('teacher.attendance.live.verify');
    
    Route::get('/analytics/statistics', [AttendanceAnalyticsController::class, 'statistics'])->name('teacher.analytics.statistics');
    Route::get('/analytics/summary-stats', [AttendanceAnalyticsController::class, 'getSummaryStats'])->name('teacher.analytics.summary');
    Route::get('/analytics/attendance-trend', [AttendanceAnalyticsController::class, 'getAttendanceTrend'])->name('teacher.analytics.trend');
    Route::get('/analytics/absenteeism-rates', [AttendanceAnalyticsController::class, 'getAbsenteeismRates'])->name('teacher.analytics.absenteeism');
    Route::get('/analytics/weekly-trend', [AttendanceAnalyticsController::class, 'getWeeklyTrend'])->name('teacher.analytics.weekly');
    Route::get('/analytics/monthly-trend', [AttendanceAnalyticsController::class, 'getMonthlyTrend'])->name('teacher.analytics.monthly');
    Route::get('/analytics/time-patterns', [AttendanceAnalyticsController::class, 'getTimePatterns'])->name('teacher.analytics.patterns');
    Route::get('/sections/list', [SectionController::class, 'getTeacherSections'])->name('teacher.sections.list');
    
    Route::get('/report', [ReportController::class, 'index'])->name('teacher.report');
    Route::post('/attendance/export/csv', [ReportController::class, 'exportCsv'])->name('teacher.attendance.export.csv');
    
 
    Route::post('/sf2/generate', [ReportController::class, 'generateSF2'])->name('teacher.sf2.generate');
    Route::post('/sf2/generate-pdf', [ReportController::class, 'generateSF2PDF'])->name('teacher.sf2.generate.pdf');
    Route::get('/sf2/options', [ReportController::class, 'getSF2Options'])->name('teacher.sf2.options');
    Route::get('/sf2/files', [ReportController::class, 'getGeneratedSF2Files'])->name('teacher.sf2.files');
    Route::post('/students/import-excel', [ImportController::class, 'preview'])->name('teacher.students.importExcel');
    Route::get('/account', [TeacherController::class, 'account'])->name('teacher.account');
    Route::put('/account', [TeacherController::class, 'update'])->name('teacher.account.update');
    Route::put('/account/password', [TeacherController::class, 'updatePassword'])->name('teacher.account.password');
    
    Route::post('/qr-verify', [AttendanceController::class, 'verifyQrAndRecordAttendance'])->name('teacher.qr.verify');
    
    Route::post('/send-sms', [MessageApiController::class, 'sendSms'])->name('teacher.send.sms');
    Route::get('/outbound-messages', [MessageApiController::class, 'getOutboundMessages'])->name('teacher.outbound.messages');
    Route::get('/message-status/{id}', [MessageApiController::class, 'getMessageStatus'])->name('teacher.message.status');
    Route::post('/check-rate-limit', [MessageApiController::class, 'checkRateLimit'])->name('teacher.check.rate.limit');
    Route::get('/test-rate-limit', [MessageApiController::class, 'testRateLimit'])->name('teacher.test.rate.limit');
    Route::get('/test-sms-gateway', [MessageApiController::class, 'testGateway'])->name('teacher.test.gateway');
    Route::get('/system/status/sms', [AdminController::class, 'checkSmsStatus'])->name('teacher.system.status.sms');
    Route::get('/get-students', [StudentManagementController::class, 'getStudentsForApi'])->name('teacher.get.students');
    
    // Attendance Session Routes
    Route::post('/attendance-session/create', [App\Http\Controllers\AttendanceSessionController::class, 'createSession'])->name('teacher.attendance.session.create');
    Route::get('/attendance-session/active', [App\Http\Controllers\AttendanceSessionController::class, 'getActiveSessions'])->name('teacher.attendance.session.active');
    Route::get('/attendance-session/today', [App\Http\Controllers\AttendanceSessionController::class, 'getTodaySession'])->name('teacher.attendance.session.today');
    Route::post('/attendance-session/{id}/close', [App\Http\Controllers\AttendanceSessionController::class, 'closeSession'])->name('teacher.attendance.session.close');

});

    
Route::middleware(['role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats'])->name('admin.dashboard.stats');
    
    Route::get('/system/status/database', [AdminController::class, 'checkDatabaseStatus'])->name('admin.system.status.database');
    Route::get('/system/status/sms', [AdminController::class, 'checkSmsStatus'])->name('admin.system.status.sms');
    Route::get('/system/status/storage', [AdminController::class, 'checkStorageStatus'])->name('admin.system.status.storage');
    Route::get('/attendance/recent', [AdminController::class, 'getRecentAttendance'])->name('admin.attendance.recent');
    
    Route::get('/manage-schools', [AdminController::class, 'manageSchools'])->name('admin.manage-schools');
    Route::get('/add-school', [AdminController::class, 'addSchoolForm'])->name('admin.add-school');
    Route::post('/store-school', [AdminController::class, 'storeSchool'])->name('admin.store-school');
    Route::get('/edit-school/{id}', [AdminController::class, 'editSchoolForm'])->name('admin.edit-school');
    Route::put('/update-school/{id}', [AdminController::class, 'updateSchool'])->name('admin.update-school');
    Route::delete('/delete-school/{id}', [AdminController::class, 'deleteSchool'])->name('admin.delete-school');
    
    Route::get('/manage-teachers', [AdminController::class, 'manageTeachers'])->name('admin.manage-teachers');
    Route::post('/store-teacher', [AdminController::class, 'storeTeacher'])->name('admin.store-teacher');
    Route::put('/teachers/{id}', [AdminController::class, 'updateTeacher'])->name('admin.update-teacher');
    Route::delete('/teachers/{id}', [AdminController::class, 'deleteTeacher'])->name('admin.delete-teacher');
    Route::post('/reassign-section', [AdminController::class, 'reassignSection'])->name('admin.reassign-section');
    Route::post('/create-section-for-teacher', [AdminController::class, 'createSectionForTeacher'])->name('admin.create-section-for-teacher');
    
    // Attendance Code Management Routes
    Route::post('/attendance-codes/generate/{teacherId}', [AdminController::class, 'generateAttendanceCode'])->name('admin.attendance-code.generate');
    Route::post('/attendance-codes/regenerate/{teacherId}', [AdminController::class, 'regenerateAttendanceCode'])->name('admin.attendance-code.regenerate');
    Route::get('/attendance-codes/status/{teacherId}', [AdminController::class, 'getAttendanceCodeStatus'])->name('admin.attendance-code.status');
    
    Route::get('/manage-school-years', [SchoolYearController::class, 'index'])->name('admin.manage-school-years');
    Route::get('/school-years/create', [SchoolYearController::class, 'create'])->name('admin.school-year.create');
    Route::post('/school-years/store', [SchoolYearController::class, 'store'])->name('admin.school-year.store');
    Route::get('/school-years/{schoolYear}/edit', [SchoolYearController::class, 'edit'])->name('admin.school-year.edit');
    Route::put('/school-years/{schoolYear}', [SchoolYearController::class, 'update'])->name('admin.school-year.update');
    Route::delete('/school-years/{schoolYear}', [SchoolYearController::class, 'destroy'])->name('admin.school-year.delete');
    Route::post('/school-years/{schoolYear}/toggle-status', [SchoolYearController::class, 'toggleStatus'])->name('admin.school-year.toggle-status');
    Route::get('/school-year/active', [SchoolYearController::class, 'getActiveSchoolYear'])->name('admin.school-year.active');
    
    Route::get('/sections/form-data', [SectionController::class, 'getFormData'])->name('admin.section.form-data');
    Route::post('/sections/store', [SectionController::class, 'store'])->name('admin.section.store');
    Route::get('/sections/{section}/edit', [SectionController::class, 'edit'])->name('admin.section.edit');
    Route::put('/sections/{section}', [SectionController::class, 'update'])->name('admin.section.update');
    Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('admin.section.destroy');
    
    Route::get('/manage-students', [AdminController::class, 'manageStudents'])->name('admin.manage-students');
    Route::get('/manage-students-new', [AdminController::class, 'manageStudentsNew'])->name('admin.manage-students-new');
    Route::post('/students/store', [AdminController::class, 'storeStudent'])->name('admin.students.store');
    Route::get('/students/{id}/edit', [AdminController::class, 'editStudent'])->name('admin.students.edit');
    Route::put('/students/{id}', [AdminController::class, 'updateStudent'])->name('admin.students.update');
    Route::put('/students/{id}/admin-update', [AdminController::class, 'updateStudentAdmin'])->name('admin.students.updateAdmin');
    Route::delete('/students/{id}', [AdminController::class, 'deleteStudent'])->name('admin.students.destroy');
    Route::delete('/students/bulk-delete', [AdminController::class, 'bulkDeleteStudents'])->name('admin.students.bulkDelete');
    Route::post('/students/bulk-export', [AdminController::class, 'bulkExportStudents'])->name('admin.students.bulkExport');
    Route::post('/students/generate-qrs', [AdminController::class, 'generateQrs'])->name('admin.students.generateQrs');
    Route::get('/students/print-qrs', [AdminController::class, 'printQrs'])->name('admin.students.printQrs');
    Route::get('/students/download-qrs', [AdminController::class, 'downloadQrs'])->name('admin.students.downloadQrs');
    Route::post('/students/{id}/generate-qr', [AdminController::class, 'generateQr'])->name('admin.students.generateQr');
    Route::get('/students/export', [AdminController::class, 'exportStudents'])->name('admin.students.export');
    
    Route::get('/students/download-template', [AdminController::class, 'downloadTemplate'])->name('admin.students.downloadTemplate');
    Route::get('/students/download-sample-data', [AdminController::class, 'downloadSampleData'])->name('admin.students.downloadSampleData');
    Route::get('/students/import', [ImportController::class, 'showUploadForm'])->name('admin.students.import.form');
    Route::post('/students/import', [ImportController::class, 'import'])->name('admin.students.import');
    Route::get('/students/import-guide', [AdminController::class, 'importGuide'])->name('admin.students.importGuide');
    Route::get('/import/upload', [ImportController::class, 'showUploadForm'])->name('import.upload.form');
    Route::post('/import/upload', [ImportController::class, 'preview'])->name('import.upload');
    Route::post('/import/import', [ImportController::class, 'import'])->name('import.import');
    
    Route::get('/school-years/{schoolYear}/schools', [AdminController::class, 'getSchoolsBySemester'])->name('admin.school-years.schools');
    Route::get('/school-years/{schoolYear}/months', [AdminController::class, 'getSemesterMonths'])->name('admin.school-years.months');
    Route::get('/schools/with-counts', [AdminController::class, 'getSchoolsWithCounts'])->name('admin.schools.with-counts');
    Route::get('/schools/{school}/teachers', [AdminController::class, 'getTeachersBySchool'])->name('admin.schools.teachers');
    Route::get('/teachers/{teacher}/sections', [AdminController::class, 'getSectionsByTeacher'])->name('admin.teachers.sections');
    
    Route::get('/attendance-reports', [AdminController::class, 'attendanceReports'])->name('admin.attendance-reports');
    Route::get('/teacher-attendance-reports', [AdminController::class, 'teacherAttendanceReports'])->name('admin.teacher-attendance-reports');
    
    Route::post('/teacher-attendance/export/csv', [AdminController::class, 'exportTeacherAttendanceCsv'])->name('admin.teacher-attendance.export.csv');
    Route::post('/teacher-attendance/export/excel', [AdminController::class, 'exportTeacherAttendanceExcel'])->name('admin.teacher-attendance.export.excel');
    Route::get('/sf2/options', [AdminController::class, 'getAdminSF2Options'])->name('admin.sf2.options');
    Route::post('/sf2/generate', [ReportController::class, 'generateSF2'])->name('admin.sf2.generate');
    Route::post('/sf2/generate-pdf', [ReportController::class, 'generateSF2PDF'])->name('admin.sf2.generate.pdf');
    Route::get('/sf2/files', [ReportController::class, 'getGeneratedSF2Files'])->name('admin.sf2.files');
    
    Route::get('/attendance', [AdminController::class, 'attendance'])->name('admin.attendance');
    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::get('/semester', [AdminController::class, 'semester'])->name('admin.semester');
    Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
    
    Route::get('/account', [AdminController::class, 'account'])->name('admin.account');
    Route::put('/account', [AdminController::class, 'updateAccount'])->name('admin.account.update');
    Route::put('/account/password', [AdminController::class, 'updatePassword'])->name('admin.account.password');
    Route::get('/refresh-csrf', [AdminController::class, 'refreshCsrf'])->name('admin.refresh-csrf');
    Route::get('/message', [AdminController::class, 'message'])->name('admin.message');
    Route::post('/send-sms', [MessageApiController::class, 'sendSms'])->name('admin.send.sms');
    Route::get('/outbound-messages', [MessageApiController::class, 'getOutboundMessages'])->name('admin.outbound.messages');
    Route::get('/message-status/{id}', [MessageApiController::class, 'getMessageStatus'])->name('admin.message.status');
    Route::post('/check-rate-limit', [MessageApiController::class, 'checkRateLimit'])->name('admin.check.rate.limit');
    Route::get('/test-rate-limit', [MessageApiController::class, 'testRateLimit'])->name('admin.test.rate.limit');
    Route::get('/test-sms-gateway', [MessageApiController::class, 'testGateway'])->name('admin.test.gateway');
    Route::get('/get-teachers', [AdminController::class, 'getTeachersForApi'])->name('admin.get.teachers');
    Route::get('/get-all-students', [AdminController::class, 'getAllStudentsForApi'])->name('admin.get.all.students');
});

Route::middleware(['role:teacher,admin'])->group(function () {
    Route::get('/student-id/print/{id}', [StudentIdController::class, 'printSingle'])->name('student.id.print');
    Route::get('/student-ids/print-all', [StudentIdController::class, 'printAll'])->name('student.ids.print.all');
    Route::get('/student-ids/print-by-teacher/{teacherId}', [StudentIdController::class, 'printByTeacher'])->name('student.ids.print.by.teacher');
    Route::get('/student-ids/print-my-students', [StudentIdController::class, 'printMyStudents'])->name('student.ids.print.my.students');
});

 Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['role:teacher'])->prefix('teacher')->group(function () {
    Route::post('/attendance-code/generate', [App\Http\Controllers\AttendanceCodeController::class, 'generate'])->name('teacher.attendance.code.generate');
    Route::get('/attendance-code/active', [App\Http\Controllers\AttendanceCodeController::class, 'getActive'])->name('teacher.attendance.code.active');
    Route::delete('/attendance-code/{id}/deactivate', [App\Http\Controllers\AttendanceCodeController::class, 'deactivate'])->name('teacher.attendance.code.deactivate');
    Route::get('/attendance-code/{id}/print', [App\Http\Controllers\AttendanceCodeController::class, 'printCode'])->name('teacher.attendance.code.print');
});


 Route::middleware(['role:teacher'])->prefix('teacher/attendance')->group(function () {
    Route::post('/record', [AttendanceController::class, 'teacherRecordAttendance'])->name('teacher.attendance.record');
    Route::get('/report', [AttendanceController::class, 'getAttendanceReport'])->name('teacher.attendance.report');
});

 
Route::get('/public/attendance', [App\Http\Controllers\PublicAttendanceController::class, 'index'])->name('public.attendance.index');
Route::get('/public/attendance/{code}', [App\Http\Controllers\PublicAttendanceController::class, 'show'])->name('public.attendance.show');

Route::post('/public/attendance/scan-qr', [App\Http\Controllers\PublicAttendanceController::class, 'scanQR'])->name('public.attendance.scan');
Route::post('/public/attendance/{code}/clear', [App\Http\Controllers\PublicAttendanceController::class, 'clearStudent'])->name('public.attendance.clear');
Route::get('/public/attendance/{code}/recent', [App\Http\Controllers\PublicAttendanceController::class, 'getRecentLogs'])->name('public.attendance.recent');
Route::get('/public/attendance/{code}/summary', [App\Http\Controllers\PublicAttendanceController::class, 'getTodaySummary'])->name('public.attendance.summary');
 
Route::get('/api/semester/time-sessions', [App\Http\Controllers\AttendanceSessionController::class, 'getTimeSessions']);
Route::get('/api/teacher-sections/{teacherId}', [App\Http\Controllers\AdminController::class, 'getTeacherSections']);
Route::get('/api/student-attendance-today/{studentId}', [AttendanceController::class, 'getStudentAttendanceToday']);
Route::get('/teacher/attendance-forecast', [App\Http\Controllers\AttendanceForecastController::class, 'index'])->name('teacher.attendance.forecast');

