@extends('admin/sidebar')
@section('title', 'Teacher Attendance Reports')
@section('content')

<!-- Custom CSS for enhanced styling -->
<style>
.filter-card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.75rem;
}

.filter-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.75rem 0.75rem 0 0;
    padding: 1.25rem;
}

.form-floating > label {
    font-weight: 500;
    color: #6c757d;
}

.form-floating > .form-select,
.form-floating > .form-control {
    border: 1.5px solid #e9ecef;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.form-floating > .form-select:focus,
.form-floating > .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.info-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    background: #f8f9fa;
    color: #6c757d;
    border: 1px solid #e9ecef;
}

.btn-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-gradient:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.filter-section {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 1px solid #e9ecef;
}

@media (max-width: 768px) {
    .filter-header {
        padding: 1rem;
    }
    
    .filter-section {
        padding: 1rem;
    }
}
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1 text-dark fw-bold">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Teacher Attendance Reports
                    </h2>
                    <p class="text-muted mb-0 small">Generate comprehensive attendance reports across schools, teachers, and sections</p>
                </div>
                <div class="text-end">
                    <div class="badge bg-primary-subtle text-primary px-3 py-2">
                        <i class="fas fa-calendar me-1"></i>
                        {{ now()->format('F d, Y') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Advanced Filters Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card">
                <div class="filter-header text-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-sliders-h me-2 fs-5"></i>
                            <h5 class="mb-0 fw-semibold">Advanced Filters</h5>
                        </div>
                        <div class="text-end">
                            <small class="opacity-75">Configure your report parameters</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form id="filterForm" method="GET" action="{{ route('admin.teacher-attendance-reports') }}">
                        <!-- Report Configuration Section -->
                        <div class="filter-section">
                            <h6 class="text-primary mb-3 fw-semibold">
                                <i class="fas fa-cog me-2"></i>Report Configuration
                            </h6>
                            <div class="row g-4">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-floating">
                                        <select name="type" id="type" class="form-select">
                                            <option value="daily" {{ request('type', 'daily') == 'daily' ? 'selected' : '' }}>Daily Report</option>
                                            <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>Monthly Summary</option>
                                            <option value="quarterly" {{ request('type') == 'quarterly' ? 'selected' : '' }}>Quarterly Analysis</option>
                                        </select>
                                        <label for="type">
                                            <i class="fas fa-chart-line text-primary me-2"></i>Report Type
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location & Personnel Section -->
                        <div class="filter-section">
                            <h6 class="text-success mb-3 fw-semibold">
                                <i class="fas fa-users me-2"></i>Location & Personnel Filters
                            </h6>
                            <div class="row g-4">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-floating">
                                        <select name="school_id" id="school_id" class="form-select">
                                            <option value="">All Schools</option>
                                            @foreach($schools as $school)
                                                <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>
                                                    {{ $school->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="school_id">
                                            <i class="fas fa-school text-success me-2"></i>School
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <span class="info-badge" id="schoolInfo">Select a school to filter teachers</span>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <div class="form-floating">
                                        <select name="teacher_id" id="teacher_id" class="form-select">
                                            <option value="">All Teachers</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                                    {{ $teacher->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="teacher_id">
                                            <i class="fas fa-chalkboard-teacher text-success me-2"></i>Teacher
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <span class="info-badge" id="teacherInfo">Select a teacher to filter sections</span>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <div class="form-floating">
                                        <select name="grade_section" id="grade_section" class="form-select">
                                            <option value="">All Sections</option>
                                            @if(isset($gradeSectionOptions))
                                                @foreach($gradeSectionOptions as $option)
                                                    @php
                                                        $parts = explode('|', $option);
                                                        $displayText = count($parts) == 2 ? "Grade {$parts[0]} - {$parts[1]}" : $option;
                                                    @endphp
                                                    <option value="{{ $option }}" {{ request('grade_section') == $option ? 'selected' : '' }}>
                                                        {{ $displayText }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <label for="grade_section">
                                            <i class="fas fa-layer-group text-success me-2"></i>Grade & Section
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <span class="info-badge" id="sectionInfo">Filter by specific section</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Period Section -->
                        <div class="filter-section">
                            <h6 class="text-info mb-3 fw-semibold">
                                <i class="fas fa-calendar-alt me-2"></i>Academic Period
                            </h6>
                            <div class="row g-4">
                                <!-- Semester Selection (Always shown, required for Quarterly) -->
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-floating">
                                        <select name="semester_id" id="semester_id" class="form-select">
                                            <option value="">All Semesters</option>
                                            @foreach($semesters as $semester)
                                                <option value="{{ $semester->id }}" {{ request('semester_id') == $semester->id ? 'selected' : '' }}>
                                                    {{ $semester->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="semester_id">
                                            <i class="fas fa-graduation-cap text-info me-2"></i>Semester
                                        </label>
                                    </div>
                                    <div class="mt-2" style="display: none;">
                                        <span class="info-badge" id="semesterInfo"></span>
                                    </div>
                                </div>

                                 <div class="col-lg-3 col-md-6" id="singleDateField" style="display:none;">
                                    <div class="form-floating">
                                        <input type="date" name="report_date" id="report_date" value="{{ request('report_date', now()->toDateString()) }}" class="form-control">
                                        <label for="report_date">
                                            <i class="fas fa-calendar text-info me-2"></i>Select Date
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <span class="info-badge">Choose specific date for daily report</span>
                                    </div>
                                </div>

                                 <div class="col-lg-3 col-md-6" id="monthField" style="display:none;">
                                    <div class="form-floating">
                                        <select name="report_month_year" id="report_month_year" class="form-select">
                                            @php
                                                $currentYear = date('Y');
                                                $currentMonth = date('n');
                                                $selectedMonthYear = request('report_month_year', $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT));
                                            @endphp
                                            @for($y = $currentYear - 2; $y <= $currentYear + 1; $y++)
                                                @for($m = 1; $m <= 12; $m++)
                                                    @php
                                                        $value = $y . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                                                        $display = date('F Y', mktime(0, 0, 0, $m, 1, $y));
                                                    @endphp
                                                    <option value="{{ $value }}" {{ $selectedMonthYear == $value ? 'selected' : '' }}>
                                                        {{ $display }}
                                                    </option>
                                                @endfor
                                            @endfor
                                        </select>
                                        <label for="report_month_year">
                                            <i class="fas fa-calendar-alt text-info me-2"></i>Month & Year
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <span class="info-badge" id="monthInfo">Choose report month and year</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end gap-3">
                            <button type="button" class="btn btn-outline-secondary px-4" onclick="clearFilters()">
                                <i class="fas fa-eraser me-2"></i>Clear All Filters
                            </button>
                            <button type="submit" class="btn btn-gradient text-white px-5">
                                <i class="fas fa-chart-bar me-2"></i>Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Preview Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div id="previewArea">
                @if(isset($records) && count($records) > 0)
                    @include('admin.teacher_report_preview', ['records' => $records, 'type' => $type ?? 'daily'])
                @else
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-chart-line fa-4x text-muted opacity-50"></i>
                            </div>
                            <h5 class="text-muted mb-2">No Report Data Available</h5>
                            <p class="text-muted mb-4">Configure your filters above and click "Generate Report" to view attendance data.</p>
                            <div class="d-flex justify-content-center gap-3">
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="fas fa-school me-1"></i>Select School
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="fas fa-chalkboard-teacher me-1"></i>Choose Teacher
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="fas fa-calendar me-1"></i>Pick Date Range
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Export Options (Show only when data is available) -->
    @if(isset($records) && count($records) > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-semibold text-dark">
                                <i class="fas fa-download text-success me-2"></i>Export Options
                            </h5>
                            <p class="text-muted mb-0 small">Download your report in various formats</p>
                        </div>
                        <div class="d-flex gap-3">
                            <form method="POST" action="{{ route('admin.teacher-attendance.export.csv') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="type" value="{{ request('type', 'daily') }}">
                                <input type="hidden" name="semester_id" value="{{ request('semester_id') }}">
                                <input type="hidden" name="teacher_id" value="{{ request('teacher_id') }}">
                                <input type="hidden" name="school_id" value="{{ request('school_id') }}">
                                <input type="hidden" name="grade_section" value="{{ request('grade_section') }}">
                                <input type="hidden" name="report_date" value="{{ request('report_date') }}">
                                <input type="hidden" name="report_month_year" value="{{ request('report_month_year') }}">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-file-csv me-2"></i>Export CSV
                                </button>
                            </form>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sf2Modal">
                                <i class="fas fa-file-excel me-2"></i>Generate SF2
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Enhanced SF2 Generation Modal -->
<div class="modal fade" id="sf2Modal" tabindex="-1" aria-labelledby="sf2ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title fw-semibold" id="sf2ModalLabel">
                    <i class="fas fa-file-excel me-2"></i>Generate SF2 Form
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Configure the parameters below to generate a detailed SF2 attendance form.
                </div>
                
                <form id="sf2Form">
                    @csrf
                    <div class="row g-4">
                        <!-- Institution Details -->
                        <div class="col-12">
                            <h6 class="text-primary mb-3 fw-semibold border-bottom pb-2">
                                <i class="fas fa-building me-2"></i>Institution Details
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="school_id" id="sf2_school" class="form-select" required>
                                    <option value="">Select School</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>
                                            {{ $school->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="sf2_school">
                                    <i class="fas fa-school text-primary me-2"></i>School
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="semester_id" id="sf2_semester" class="form-select" required>
                                    <option value="">Select Semester</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}" {{ request('semester_id') == $semester->id ? 'selected' : '' }}>
                                            {{ $semester->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="sf2_semester">
                                    <i class="fas fa-graduation-cap text-primary me-2"></i>Semester
                                </label>
                            </div>
                        </div>

                        <!-- Personnel & Section -->
                        <div class="col-12">
                            <h6 class="text-success mb-3 fw-semibold border-bottom pb-2">
                                <i class="fas fa-users me-2"></i>Personnel & Section Selection
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="teacher_id" id="sf2_teacher" class="form-select">
                                    <option value="">All Teachers</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="sf2_teacher">
                                    <i class="fas fa-chalkboard-teacher text-success me-2"></i>Teacher (Optional)
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="grade_section" id="sf2_grade_section" class="form-select">
                                    <option value="">All Sections</option>
                                    @if(isset($gradeSectionOptions))
                                        @foreach($gradeSectionOptions as $option)
                                            @php
                                                $parts = explode('|', $option);
                                                $displayText = count($parts) == 2 ? "Grade {$parts[0]} - {$parts[1]}" : $option;
                                            @endphp
                                            <option value="{{ $option }}" {{ request('grade_section') == $option ? 'selected' : '' }}>
                                                {{ $displayText }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <label for="sf2_grade_section">
                                    <i class="fas fa-layer-group text-success me-2"></i>Grade & Section (Optional)
                                </label>
                            </div>
                        </div>

                        <!-- Time Period -->
                        <div class="col-12">
                            <h6 class="text-info mb-3 fw-semibold border-bottom pb-2">
                                <i class="fas fa-calendar-alt me-2"></i>Report Period
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="month" id="sf2_month" class="form-select" required>
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ request('report_month', date('n')) == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                                <label for="sf2_month">
                                    <i class="fas fa-calendar-alt text-info me-2"></i>Month
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="year" id="sf2_year" class="form-select" required>
                                    @for($y = date('Y') - 5; $y <= date('Y') + 1; $y++)
                                        <option value="{{ $y }}" {{ request('report_year', date('Y')) == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                                <label for="sf2_year">
                                    <i class="fas fa-calendar text-info me-2"></i>Year
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-gradient text-white px-4" id="generateSF2Btn">
                    <i class="fas fa-file-excel me-2"></i>Generate SF2
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SF2 Result Modal -->
<div class="modal fade" id="sf2ResultModal" tabindex="-1" aria-labelledby="sf2ResultModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-semibold" id="sf2ResultModalLabel">
                    <i class="fas fa-check-circle me-2"></i>SF2 Generated Successfully
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-success border-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="sf2ResultMessage">SF2 file has been generated successfully!</span>
                </div>
                <div class="d-grid gap-3">
                    <a href="#" id="downloadExcelBtn" class="btn btn-success">
                        <i class="fas fa-download me-2"></i>Download Excel File
                    </a>
                    <button type="button" class="btn btn-outline-primary" id="generatePdfBtn">
                        <i class="fas fa-file-pdf me-2"></i>Generate PDF Version
                    </button>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    updateFilterFields();
    setupCascadingDropdowns();
    
    // Update fields when type changes
    $('#type').change(updateFilterFields);
    
    // Update months when semester changes
    $('#semester_id').change(updateMonthsForSemester);
    $('#sf2_semester').change(updateSF2MonthsForSemester);
});

function updateFilterFields() {
    var type = $('#type').val();
    
    // Hide all date/period fields first
    $('#singleDateField, #monthField').hide();
    
    // Show fields based on report type
    if(type === 'daily') {
        // Daily: Show only single date selection
        $('#singleDateField').show();
    } else if(type === 'monthly') {
        // Monthly: Show combined month and year selection
        $('#monthField').show();
    } else if(type === 'quarterly') {
        // Quarterly: Only semester selection (already visible, no additional fields needed)
        // Semester field is always visible
    }
}

function setupCascadingDropdowns() {
    // Load enhanced school data on page load
    loadEnhancedSchoolData();
    
    // When school changes, update teachers
    $('#school_id').change(function() {
        var schoolId = $(this).val();
        updateTeachersBySchool(schoolId);
        // Clear dependent dropdowns
        $('#teacher_id').html('<option value="">All Teachers</option>');
        $('#grade_section').html('<option value="">All Sections</option>');
    });
    
    // When teacher changes, update grade sections
    $('#teacher_id').change(function() {
        var teacherId = $(this).val();
        updateGradeSectionsByTeacher(teacherId);
    });
    
    // SF2 Modal cascading dropdowns
    $('#sf2_school').change(function() {
        var schoolId = $(this).val();
        updateSF2Teachers(schoolId);
        // Clear dependent dropdowns
        $('#sf2_teacher').html('<option value="">All Teachers</option>');
        $('#sf2_grade_section').html('<option value="">All Sections</option>');
    });
    
    $('#sf2_teacher').change(function() {
        var teacherId = $(this).val();
        updateSF2GradeSections(teacherId);
    });
}

function loadEnhancedSchoolData() {
    $.get('/admin/schools/with-counts')
        .done(function(schools) {
            var $schoolSelect = $('#school_id');
            var currentValue = $schoolSelect.val();
            
            $schoolSelect.html('<option value="">All Schools</option>');
            schools.forEach(function(school) {
                var optionText = school.teachers_count > 0 
                    ? `${school.name} (${school.teachers_count} teachers)`
                    : `${school.name} (No teachers)`;
                    
                var $option = $(`<option value="${school.id}">${optionText}</option>`);
                if (school.teachers_count === 0) {
                    $option.css('color', '#999');
                }
                $schoolSelect.append($option);
            });
            
            // Update info span
            $('#schoolInfo').text(`${schools.length} schools available`);
            
            // Restore previous selection
            if (currentValue) {
                $schoolSelect.val(currentValue);
            }
        })
        .fail(function() {
            showError('Failed to load school data');
        });
}

function updateTeachersBySchool(schoolId) {
    var $teacherSelect = $('#teacher_id');
    
    if (!schoolId) {
        $teacherSelect.html('<option value="">All Teachers</option>');
        $('#teacherInfo').text('Select a school to filter teachers');
        return;
    }
    
    $teacherSelect.html('<option value="">Loading...</option>').prop('disabled', true);
    
    $.get(`/admin/schools/${schoolId}/teachers`)
        .done(function(teachers) {
            $teacherSelect.html('<option value="">All Teachers</option>').prop('disabled', false);
            
            if (teachers.length === 0) {
                $teacherSelect.append('<option value="" disabled>No teachers found in this school</option>');
                $('#teacherInfo').text('No teachers available').removeClass('info-badge').addClass('badge bg-warning');
                return;
            }
            
            $('#teacherInfo').text(`${teachers.length} teachers available`).removeClass('badge bg-warning').addClass('info-badge');
            
            teachers.forEach(function(teacher) {
                var optionText = teacher.sections_count > 0 
                    ? `${teacher.name} (${teacher.sections_count} sections)`
                    : `${teacher.name} (No sections)`;
                    
                if (teacher.sections_preview) {
                    optionText += ` - ${teacher.sections_preview}`;
                }
                
                var $option = $(`<option value="${teacher.id}">${optionText}</option>`);
                if (teacher.sections_count === 0) {
                    $option.css('color', '#999');
                }
                $teacherSelect.append($option);
            });
        })
        .fail(function() {
            $teacherSelect.html('<option value="">Error loading teachers</option>').prop('disabled', false);
            showError('Failed to load teachers for selected school');
        });
}

function updateGradeSectionsByTeacher(teacherId) {
    var $sectionSelect = $('#grade_section');
    
    if (!teacherId) {
        $sectionSelect.html('<option value="">All Sections</option>');
        $('#sectionInfo').text('Select a teacher to filter sections');
        return;
    }
    
    $sectionSelect.html('<option value="">Loading...</option>').prop('disabled', true);
    
    $.get(`/api/teacher-sections/${teacherId}`)
        .done(function(sections) {
            $sectionSelect.html('<option value="">All Sections</option>').prop('disabled', false);
            
            if (sections.length === 0) {
                $sectionSelect.append('<option value="" disabled>No sections assigned to this teacher</option>');
                $('#sectionInfo').text('No sections available').removeClass('info-badge').addClass('badge bg-warning');
                return;
            }
            
            $('#sectionInfo').text(`${sections.length} sections available`).removeClass('badge bg-warning').addClass('info-badge');
            
            sections.forEach(function(section) {
                var optionText = `${section.display_name} (${section.students_count} students)`;
                if (section.semester_name && section.semester_name !== 'No Semester') {
                    optionText += ` - ${section.semester_name}`;
                }
                
                var $option = $(`<option value="${section.value}">${optionText}</option>`);
                if (section.students_count === 0) {
                    $option.css('color', '#999');
                }
                $sectionSelect.append($option);
            });
        })
        .fail(function() {
            $sectionSelect.html('<option value="">Error loading sections</option>').prop('disabled', false);
            showError('Failed to load sections for selected teacher');
        });
}

function updateSF2Teachers(schoolId) {
    var $teacherSelect = $('#sf2_teacher');
    
    if (!schoolId) {
        $teacherSelect.html('<option value="">All Teachers</option>');
        return;
    }
    
    $teacherSelect.html('<option value="">Loading...</option>').prop('disabled', true);
    
    $.get(`/admin/schools/${schoolId}/teachers`)
        .done(function(teachers) {
            $teacherSelect.html('<option value="">All Teachers</option>').prop('disabled', false);
            
            teachers.forEach(function(teacher) {
                var optionText = teacher.sections_count > 0 
                    ? `${teacher.name} (${teacher.sections_count} sections)`
                    : `${teacher.name} (No sections)`;
                    
                $teacherSelect.append(`<option value="${teacher.id}">${optionText}</option>`);
            });
        })
        .fail(function() {
            $teacherSelect.html('<option value="">Error loading teachers</option>').prop('disabled', false);
        });
}

function updateSF2GradeSections(teacherId) {
    var $sectionSelect = $('#sf2_grade_section');
    
    if (!teacherId) {
        $sectionSelect.html('<option value="">All Sections</option>');
        return;
    }
    
    $sectionSelect.html('<option value="">Loading...</option>').prop('disabled', true);
    
    $.get(`/api/teacher-sections/${teacherId}`)
        .done(function(sections) {
            $sectionSelect.html('<option value="">All Sections</option>').prop('disabled', false);
            
            sections.forEach(function(section) {
                var optionText = `${section.display_name} (${section.students_count} students)`;
                $sectionSelect.append(`<option value="${section.value}">${optionText}</option>`);
            });
        })
        .fail(function() {
            $sectionSelect.html('<option value="">Error loading sections</option>').prop('disabled', false);
        });
}

function updateMonthsForSemester() {
    var semesterId = $('#semester_id').val();
    
    if (!semesterId) {
        $('#semesterInfo').text('Select semester to filter months');
        return;
    }
    
    $.get(`/admin/semesters/${semesterId}/months`)
        .done(function(data) {
            var $monthSelect = $('#report_month');
            var currentMonth = $monthSelect.val();
            
            $monthSelect.html('');
            
            if (data.months && data.months.length > 0) {
                data.months.forEach(function(month) {
                    var option = `<option value="${month.number}">${month.name}</option>`;
                    $monthSelect.append(option);
                });
                
                // Try to restore selection or select first valid month
                if (data.months.find(m => m.number == currentMonth)) {
                    $monthSelect.val(currentMonth);
                } else {
                    $monthSelect.val(data.months[0].number);
                }
                
                $('#semesterInfo').text(`${data.semester_name} (${data.date_range})`);
                $('#monthInfo').text(`${data.months.length} months available in this semester`);
            } else {
                $monthSelect.append('<option value="">No months available</option>');
                $('#monthInfo').text('No valid months in this semester');
            }
        })
        .fail(function() {
            showError('Failed to load months for selected semester');
        });
}

function updateSF2MonthsForSemester() {
    var semesterId = $('#sf2_semester').val();
    
    if (!semesterId) return;
    
    $.get(`/admin/semesters/${semesterId}/months`)
        .done(function(data) {
            var $monthSelect = $('#sf2_month');
            var currentMonth = $monthSelect.val();
            
            $monthSelect.html('');
            
            if (data.months && data.months.length > 0) {
                data.months.forEach(function(month) {
                    var option = `<option value="${month.number}">${month.name}</option>`;
                    $monthSelect.append(option);
                });
                
                // Try to restore selection or select first valid month
                if (data.months.find(m => m.number == currentMonth)) {
                    $monthSelect.val(currentMonth);
                } else {
                    $monthSelect.val(data.months[0].number);
                }
            }
        });
}

function clearFilters() {
    $('#filterForm')[0].reset();
    updateFilterFields();
    loadEnhancedSchoolData();
    window.location.href = '{{ route("admin.teacher-attendance-reports") }}';
}

// SF2 Generation
$('#generateSF2Btn').click(function() {
    var formData = new FormData($('#sf2Form')[0]);
    
    // Validation
    if (!formData.get('school_id') || !formData.get('semester_id') || 
        !formData.get('month') || !formData.get('year')) {
        showError('Please fill in all required fields (School, Semester, Month, Year)');
        return;
    }
    
    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Generating...');
    
    $.post('{{ route("admin.sf2.generate") }}', formData, {
        processData: false,
        contentType: false
    })
    .done(function(response) {
        if (response.success) {
            $('#sf2Modal').modal('hide');
            $('#sf2ResultMessage').text(response.message);
            $('#downloadExcelBtn').attr('href', response.download_url);
            $('#sf2ResultModal').modal('show');
        } else {
            showError(response.message || 'Failed to generate SF2');
        }
    })
    .fail(function(xhr) {
        var message = xhr.responseJSON?.message || 'An error occurred while generating SF2';
        showError(message);
    })
    .always(function() {
        $('#generateSF2Btn').prop('disabled', false).html('<i class="fas fa-file-excel me-1"></i>Generate SF2');
    });
});

function showError(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert-danger').remove();
    
    // Add new alert to container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-hide after 8 seconds
    setTimeout(function() {
        $('.alert-danger').fadeOut();
    }, 8000);
    
    // Scroll to top to show the error
    $('html, body').animate({ scrollTop: 0 }, 500);
}
</script>

@endsection
