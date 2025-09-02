@extends('teacher/sidebar')
@section('title', 'Dashboard')
@section('content')

@php
    $today = \Carbon\Carbon::now();
    $isActiveSemester = false;
    $semesterData = [];
    
    if($currentSemester) {
        $startDate = \Carbon\Carbon::parse($currentSemester->start_date);
        $endDate = \Carbon\Carbon::parse($currentSemester->end_date);
        
        if($today->between($startDate, $endDate)) {
            $isActiveSemester = true;
        }
    }
    
    if($currentSemester) {
        $semesterData = [
            'id' => $currentSemester->id,
            'name' => $currentSemester->name,
            'start_date' => $currentSemester->start_date,
            'end_date' => $currentSemester->end_date,
            'am_time_in_start_input' => $currentSemester->am_time_in_start_input ?? '',
            'am_time_in_end_input' => $currentSemester->am_time_in_end_input ?? '',
            'pm_time_out_start_input' => $currentSemester->pm_time_out_start_input ?? '',
            'pm_time_out_end_input' => $currentSemester->pm_time_out_end_input ?? '',
        ];
    }
    
    $displayData = [
        'semester_name' => $isActiveSemester ? $currentSemester->name : 'No Active Semester',
        'school_name' => auth()->user()->school ? auth()->user()->school->name : 'N/A',
        'school_year' => $isActiveSemester ? \Carbon\Carbon::parse($currentSemester->start_date)->format('Y') . ' - ' . \Carbon\Carbon::parse($currentSemester->end_date)->format('Y') : 'N/A',
        'date_range' => $isActiveSemester ? \Carbon\Carbon::parse($currentSemester->start_date)->format('M j, Y') . ' – ' . \Carbon\Carbon::parse($currentSemester->end_date)->format('M j, Y') : 'Not Available',
        'am_time_in_start_display' => $isActiveSemester ? (\Carbon\Carbon::parse($currentSemester->morning_period_start ?? '07:00:00')->format('g:i A')) : 'N/A',
        'am_time_in_end_display' => $isActiveSemester ? (\Carbon\Carbon::parse($currentSemester->morning_period_end ?? '11:30:00')->format('g:i A')) : 'N/A',
        'am_time_out_start_display' => 'N/A', // Not used with new semester structure
        'am_time_out_end_display' => 'N/A', // Not used with new semester structure
        'pm_time_in_start_display' => $isActiveSemester ? (\Carbon\Carbon::parse($currentSemester->afternoon_period_start ?? '13:00:00')->format('g:i A')) : 'N/A',
        'pm_time_in_end_display' => $isActiveSemester ? (\Carbon\Carbon::parse($currentSemester->afternoon_period_end ?? '17:00:00')->format('g:i A')) : 'N/A',
        'pm_time_out_start_display' => 'N/A', // Not used with new semester structure
        'pm_time_out_end_display' => 'N/A', // Not used with new semester structure
        'student_count' => $isActiveSemester ? ($studentCount ?? 0) : 0,
        'present_count' => $isActiveSemester ? ($presentCount ?? 0) : 0,
        'absent_count' => $isActiveSemester ? ($absentCount ?? 0) : 0,
        'incomplete_profiles_count' => $isActiveSemester ? (isset($studentsWithMissingInfo) ? $studentsWithMissingInfo->count() : 0) : 0,
        'most_punctual' => $isActiveSemester ? ($mostPunctual ?? null) : null,
        'most_absent' => $isActiveSemester ? ($mostAbsent ?? null) : null,
        'students_with_missing_info' => $isActiveSemester ? ($studentsWithMissingInfo ?? collect()) : collect(),
    ];
@endphp

<link rel="stylesheet" href="{{ asset('css/compact-layout.css') }}">

<div class="compact-layout">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <span class="me-2">🏫</span>
                    Teacher Dashboard
                </h4>
                <p class="subtitle mb-0">Welcome back, {{ Auth::user()->name ?? 'Teacher' }}!</p>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        @if($semesters->count() > 0)
         <div class="row g-3 mb-3">
            <div class="col-md-7">
                <div class="card shadow-sm h-100" style="background: linear-gradient(135deg, #4776e6 0%, #8e54e9 100%); position: relative; overflow: hidden; min-height: 200px;">
                     <div class="school-logo-overlay">
                        @if(auth()->user()->school && auth()->user()->school->logo)
                            <img src="{{ asset('storage/' . auth()->user()->school->logo) }}" 
                                 alt="{{ auth()->user()->school->name ?? 'School' }} Logo" 
                                 class="school-logo-positioned"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="logo-fallback-positioned" style="display: none;">
                                <i class="fas fa-school"></i>
                            </div>
                        @else
                            <div class="logo-fallback-positioned">
                                <i class="fas fa-school"></i>
                            </div>
                        @endif
                    </div>
                    
                    <div class="card-body text-white p-3 d-flex flex-column">
                        <h5 class="card-title text-white mb-3 d-flex align-items-center">
                            {{ $displayData['semester_name'] }}  &nbsp;
                            <a href="{{ route('teacher.semesters') }}" class="">
                                <i class="fas fa-eye me-1 text-dark"></i>
                            </a>
                       </h5>
                        <div class="row flex-grow-1">
                            <div class="col-md-12">
                                <p class="mb-1"><strong>School:</strong> {{ $displayData['school_name'] }}</p>
                                <p class="mb-1"><strong>Year:</strong> {{ $displayData['school_year'] }}</p>
                                <p class="mb-1"><strong>Period:</strong> {{ $displayData['date_range'] }}</p>
                                <p class="mb-1"><strong>Morning Period:</strong> 
                                    <span class="badge bg-success">{{ $displayData['am_time_in_start_display'] }} - {{ $displayData['am_time_in_end_display'] }}</span>
                                </p>
                                <p class="mb-0"><strong>Afternoon Period:</strong> 
                                    <span class="badge bg-warning">{{ $displayData['pm_time_in_start_display'] }} - {{ $displayData['pm_time_in_end_display'] }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Today's Session Card -->
            <div class="col-md-5">
                <div class="card shadow-sm h-100" style="min-height: 200px;">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title mb-3 text-center fw-bold">
                            <i class="fas fa-calendar-day me-1"></i>Today's Session
                        </h6>
                        @if($todaySession)
                            <div class="text-center mb-2">
                                <div class="fw-bold text-primary">{{$todaySession->session_name}}</div>
                                <small class="text-muted">{{ $todaySession->semester->name ?? 'Unknown' }}</small>
                            </div>
                            
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="border-end">
                                        <div class="fw-bold text-success">{{ $todaySession->started_at->format('M j') }}</div>
                                        <small class="text-muted">Created</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-bold text-info">{{ $todaySession->attendance_count ?? 0 }}</div>
                                    <small class="text-muted">Scanned</small>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-auto">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-secondary" 
                                        data-url="{{ $todaySession->getPublicUrl() }}" data-action="copy"
                                        title="Copy URL">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <a href="{{ $todaySession->getPublicUrl() }}" target="_blank" class="btn btn-success">
                                        <i class="fas fa-external-link-alt me-1"></i>Open Link
                                    </a>
                                </div>
                                <a href="{{ route('teacher.attendance') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        @else
                            <div class="text-center flex-grow-1 d-flex flex-column justify-content-center">
                                <p class="text-muted mb-3">No active session today</p>
                                <div class="d-grid gap-2 mt-auto">
                                    <a href="{{ route('teacher.attendance') }}" class="btn btn-success">
                                        <i class="fas fa-plus me-1"></i>Create Session
                                    </a>
                                    <a href="{{ route('teacher.attendance') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View All Sessions
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Stats -->
        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('teacher.students') }}" class="text-decoration-none">
                    <div class="card stat-card text-white bg-primary stat-card-clickable">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="h4 mb-0" id="myStudents">{{ $myStudents ?? 0 }}</div>
                                    <small>My Students</small>
                                </div>
                                <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: 100%"></div>
                            </div>
                           
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('teacher.students') }}" class="text-decoration-none">
                    <div class="card stat-card text-white bg-success stat-card-clickable">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="h4 mb-0" id="mySections">{{ $mySections ?? 0 }}</div>
                                    <small>My Sections</small>
                                </div>
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: 100%"></div>
                            </div>
                          
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('teacher.attendance') }}" class="text-decoration-none">
                    <div class="card stat-card text-white bg-info stat-card-clickable">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="h4 mb-0" id="todayPresent">{{ $todayPresent ?? 0 }}</div>
                                    <small>Present Today</small>
                                </div>
                                <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: {{ $todayPresent > 0 && $myStudents > 0 ? min(($todayPresent / $myStudents) * 100, 100) : 0 }}%"></div>
                            </div>
                           
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('teacher.attendance') }}" class="text-decoration-none">
                    <div class="card stat-card text-white bg-warning stat-card-clickable">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="h4 mb-0" id="attendanceRate">{{ $attendanceRate ?? '0%' }}</div>
                                    <small>Attendance Rate</small>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-75"></i>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: {{ str_replace('%', '', $attendanceRate ?? '0') }}%"></div>
                            </div>
                           
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- My Sections Overview -->
        @if(isset($teacherSections) && $teacherSections->count() > 0)
        <div class="card mb-3">
            <div class="card-header">
                <i class="fas fa-chalkboard me-1"></i>My Sections
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($teacherSections as $section)
                    <div class="col-lg-4 col-md-6">
                        <div class="card section-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">{{ $section->name }}</h6>
                                        <small class="text-muted">{{ $section->name }} - Grade {{ $section->gradelevel }}</small>
                                    </div>
                                    <span class="badge bg-primary">{{ $section->students_count ?? 0 }} students</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Today: {{ $section->present_today ?? 0 }}/{{ $section->students_count ?? 0 }}
                                    </small>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('teacher.students', ['section_id' => $section->id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="{{ route('teacher.attendance', ['section_id' => $section->id]) }}" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-clipboard-check"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @else
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5 class="text-muted">No Sections Assigned</h5>
                <p class="text-muted">Please contact the administrator to assign sections to your account.</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-refresh me-1"></i>Check Again
                </button>
            </div>
        </div>
        @endif

        <!-- Students with Missing Information Section -->
        @if($isActiveSemester && $displayData['students_with_missing_info']->count() > 0)
        <div class="row mb-3">
            <div class="col-12">
                <div class="card shadow-sm card-compact">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 fs-7">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Students with Missing Information ({{ $displayData['students_with_missing_info']->count() }} students)
                        </h6>
                        <button class="btn btn-compact btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#missingInfoTable" aria-expanded="false">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="collapse show" id="missingInfoTable">
                        <div class="card-body p-0">
                            <div class="scrollable-table-container" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-striped table-compact mb-0">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th style="width: 50px;">No.</th>
                                            <th style="width: 80px;">Student ID</th>
                                            <th style="width: 200px;">Name</th>
                                            <th style="width: 120px;">Section</th>
                                            <th style="min-width: 200px;">Missing Information</th>
                                            <th style="width: 80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($displayData['students_with_missing_info'] as $index => $student)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $student->id_no ?? 'N/A' }}</td>
                                            <td class="student-name-cell" title="{{ $student->name }}">
                                                <div class="text-bold" style="max-width: 180px;">{{ $student->name }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary" style="font-size: 0.85em;">
                                                    Grade {{ $student->grade_level ?? 'N/A' }}
                                                </div>
                                                <small class="text-muted">{{ $student->section_name ?? 'No Section' }}</small>
                                            </td>
                                            <td>
                                                <div class="missing-info-badges d-flex flex-wrap gap-1">
                                                    @if(empty($student->picture))
                                                        <span class="badge bg-danger">Picture</span>
                                                    @endif
                                                    @if(empty($student->qr_code))
                                                        <span class="badge bg-warning">QR Code</span>
                                                    @endif
                                                    @if(empty($student->cp_no))
                                                        <span class="badge bg-info">Contact</span>
                                                    @endif
                                                    @if(empty($student->address))
                                                        <span class="badge bg-secondary">Address</span>
                                                    @endif
                                                    @if(empty($student->contact_person_name))
                                                        <span class="badge bg-primary">Emergency Contacts</span>
                                                    @endif
                                                    @if(empty($student->gender))
                                                        <span class="badge bg-dark">Gender</span>
                                                    @endif
                                                    @if(empty($student->age))
                                                        <span class="badge bg-light text-dark">Age</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('teacher.students.edit', $student->id) }}" 
                                                    class="btn btn-compact btn-primary" title="View details">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-light text-muted text-center py-2">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Click on any "Update" button to edit student information and complete missing fields
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @elseif(!$isActiveSemester && $semesters->count() > 0)
        <div class="row mb-3">
            <div class="col-12">
                <div class="card shadow-sm border-warning card-compact">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-calendar-times fa-2x text-warning mb-2"></i>
                        <h6 class="text-warning mb-2">No Active Semester</h6>
                        <p class="text-muted mb-3 fs-8">
                            The current date ({{ $today->format('M j, Y') }}) is outside any semester date range.
                        </p>
                        <a href="{{ route('teacher.semesters') }}" class="btn btn-warning btn-compact">
                            <i class="fas fa-plus me-1"></i>Add or Manage Semester
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @include('teacher.statistics')

   
      
    </div>
</div>


<style>
.missing-info-badges .badge {
    font-size: 0.7em;
    margin: 1px;
    padding: 0.25em 0.4em;
}

.student-name-cell {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.analytics-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.analytics-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.performance-card {
    min-height: 180px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.performance-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,.1);
}

.scrollable-table-container {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.border-primary {
    border-left: 4px solid #0d6efd !important;
}

.border-success {
    border-left: 4px solid #198754 !important;
}

.border-danger {
    border-left: 4px solid #dc3545 !important;
}

.border-warning {
    border-left: 4px solid #ffc107 !important;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.progress {
    background-color: rgba(0,0,0,.1);
}

.btn-compact {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Clickable stat cards */
.stat-card-clickable {
    transition: all 0.3s ease;
    cursor: pointer;
}

.stat-card-clickable:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

a:hover .stat-card-clickable {
    text-decoration: none !important;
}

a .stat-card-clickable * {
    color: inherit !important;
}

/* Positioned School Logo (like blue circle) */
.school-logo-overlay {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 100px;
    height: 100px;
    z-index: 10;
}

.school-logo-positioned {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
    border: 3px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
}

.logo-fallback-positioned {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
    border: 3px solid rgba(255, 255, 255, 0.3);
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.5rem;
    backdrop-filter: blur(8px);
}
</style>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Success
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="successModalBody">
                <!-- Success message will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Error
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="errorModalBody">
                <!-- Error message will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="confirmModalLabel">
                    <i class="fas fa-question-circle me-2"></i>Confirm Action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                <!-- Confirmation message will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmModalAction">Confirm</button>
            </div>
        </div>
    </div>
</div>

 

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Dashboard Functions
function refreshDashboard() {
    location.reload();
}

function filterActivity(type) {
    // Implementation for filtering activities
    console.log('Filter activity by:', type);
    
    const activities = document.querySelectorAll('.activity-item');
    activities.forEach(activity => {
        if (type === 'all') {
            activity.style.display = 'flex';
        } else {
            // Show/hide based on activity type
            const hasType = activity.dataset.type === type;
            activity.style.display = hasType ? 'flex' : 'none';
        }
    });
}



// Attendance Chart
@if(isset($attendanceChartData))
@php
    $defaultLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
    $defaultData = [0, 0, 0, 0, 0];
@endphp
const ctx = document.getElementById('attendanceChart').getContext('2d');
const attendanceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($attendanceChartData['labels'] ?? $defaultLabels),
        datasets: [{
            label: 'Attendance Rate',
            data: @json($attendanceChartData['data'] ?? $defaultData),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
@endif

// Session Management Functions - Removed (redirecting to teacher.attendance)

// Utility Functions
function showSuccess(message) {
    document.getElementById('successModalBody').innerHTML = `
        <div class="alert alert-success mb-0">
            <i class="fas fa-check-circle me-2"></i>${message}
        </div>
    `;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('successModal')).show();
}

function showError(message) {
    document.getElementById('errorModalBody').innerHTML = `
        <div class="alert alert-danger mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>${message}
        </div>
    `;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('errorModal')).show();
}

function showConfirm(message, callback) {
    document.getElementById('confirmModalBody').innerHTML = `
        <div class="alert alert-warning mb-0">
            <i class="fas fa-question-circle me-2"></i>${message}
        </div>
    `;
    
    const confirmButton = document.getElementById('confirmModalAction');
    confirmButton.onclick = function() {
        bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
        if (callback) callback();
    };
    
    bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal')).show();
}

// Toggle Missing Students Table
function toggleMissingStudents() {
    const tableContainer = document.getElementById('missingStudentsTable');
    const toggleBtn = document.querySelector('[onclick="toggleMissingStudents()"]');
    const icon = toggleBtn.querySelector('i');
    
    if (tableContainer.style.display === 'none') {
        tableContainer.style.display = 'block';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Hide Details';
    } else {
        tableContainer.style.display = 'none';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        toggleBtn.innerHTML = '<i class="fas fa-eye me-1"></i>Show Details';
    }
}

// Auto-refresh functionality (optional)
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh every 5 minutes (300000 ms)
    // setInterval(() => {
    //     location.reload();
    // }, 300000);
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

</script>

@endsection
