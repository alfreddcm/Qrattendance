@extends('teacher/sidebar')
@section('title', 'Dashboard')
@section('content')

@php
    $today = \Carbon\Carbon::now();
    $currentSemester = null;
    $isActiveSemester = false;
    $semesterData = [];
    
    // Check if there's a semester that contains today's date
    if($semesters && $semesters->count() > 0) {
        foreach($semesters as $semester) {
            $startDate = \Carbon\Carbon::parse($semester->start_date);
            $endDate = \Carbon\Carbon::parse($semester->end_date);
            
            if($today->between($startDate, $endDate)) {
                $currentSemester = $semester;
                $isActiveSemester = true;
                break;
            }
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
            <div class="col-12">
                <div class="card shadow-sm" style="background: linear-gradient(135deg, #4776e6 0%, #8e54e9 100%); position: relative; overflow: hidden;">
                    <div class="card-body text-white p-3">
                        <div class="row align-items-center">
                            <div class="col-md-6" style="padding-right: 100px;">
                                <h5 class="card-title text-white mb-2 d-flex align-items-center">
                                    {{ $displayData['semester_name'] }}  &nbsp;
                                    <a href="{{ route('teacher.semesters') }}" class="">
                                        <i class="fas fa-eye me-1 text-dark"></i>
                                    </a>
                               </h5>
                                <div class="col">
                                    <p class="mb-1"><strong>School:</strong> {{ $displayData['school_name'] }}</p>
                                    <p class="mb-0"><strong>Year:</strong> {{ $displayData['school_year'] }}</p>
                                    <p class="mb-0"><strong>Period:</strong> {{ $displayData['date_range'] }}</p>
                                    <p class="mb-1"><strong>Morning Period:</strong> 
                                        <span class="badge bg-success">{{ $displayData['am_time_in_start_display'] }} - {{ $displayData['am_time_in_end_display'] }}</span>
                                    </p>
                                    <p class="mb-1"><strong>Afternoon Period:</strong> 
                                        <span class="badge bg-warning">{{ $displayData['pm_time_in_start_display'] }} - {{ $displayData['pm_time_in_end_display'] }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Today's Session Embedded -->
                                <div class="bg-white bg-opacity-15 rounded p-2">
                                    <h6 class="text-dark mb-2 text-center fw-bold">
                                        <i class="fas fa-calendar-day me-1"></i>Today's Session
                                    </h6>
                                    @if($todaySession)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless text-dark mb-2">
                                                <thead>
                                                    <tr class="border-bottom border-dark">
                                                        <th class="text-dark fs-7 fw-bold">Session</th>
                                                        <th class="text-dark fs-7 fw-bold">Created</th>
                                                        <th class="text-dark fs-7 fw-bold">Scanned</th>
                                                        <th class="text-dark fs-7 fw-bold">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-dark">
                                                            <strong>{{$todaySession->session_name}}</strong>
                                                            <br><small class="text-muted">{{ $todaySession->semester->name ?? 'Unknown' }}</small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success fs-8">
                                                                {{ $todaySession->started_at->format('M j') }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info fs-8">{{ $todaySession->attendance_count ?? 0 }}</span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button class="btn btn-outline-dark btn-compact" 
                                                                    data-url="{{ $todaySession->getPublicUrl() }}" data-action="copy"
                                                                    title="Copy URL">
                                                                    <i class="fas fa-copy"></i>
                                                                </button>
                                                                <button class="btn btn-outline-danger btn-compact" 
                                                                    data-session-id="{{ $todaySession->id }}" data-action="close" 
                                                                    title="Close Session">
                                                                    <i class="fas fa-stop"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center">
                                            <a href="{{ $todaySession->getPublicUrl() }}" target="_blank" class="btn btn-success btn-compact me-2">
                                                <i class="fas fa-external-link-alt me-1"></i>Open Link
                                            </a>
                                            <a href="{{ route('teacher.attendance') }}" class="btn btn-outline-dark btn-compact">
                                                <i class="fas fa-eye me-1"></i>View All
                                            </a>
                                        </div>
                                    @else
                                        <div class="text-center py-2">
                                            <p class="text-dark mb-2 fs-7">No active session</p>
                                            <button class="btn btn-success btn-compact me-2" onclick="showCreateSessionModal()">
                                                <i class="fas fa-plus me-1"></i>Get Link
                                            </button>
                                            <a href="{{ route('teacher.attendance') }}" class="btn btn-outline-dark btn-compact">
                                                <i class="fas fa-eye me-1"></i>View All
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Stats -->
        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-primary">
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
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0" id="mySections">{{ $mySections ?? 0 }}</div>
                                <small>My Sections</small>
                            </div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: {{ $mySections > 0 ? min(($mySections / 5) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-info">
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
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-warning">
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

   
        @if(isset($attendanceChartData))
        <div class="card mt-3">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>Weekly Attendance Trend
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" height="60"></canvas>
            </div>
        </div>
        @endif
    </div>
</div>

@include('teacher.statistics')

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
</style>

<!-- Create Attendance Session Modal -->
<div class="modal fade" id="createSessionModal" tabindex="-1" aria-labelledby="createSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSessionModalLabel">
                    <i class="fas fa-qrcode me-2"></i>Create Attendance Session
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createSessionForm">
                    @csrf
                    <input type="hidden" id="semester_id" name="semester_id" value="{{ $isActiveSemester ? $currentSemester->id : '' }}">
                    
                    <div class="mb-3">
                        <label for="session_name" class="form-label">Session Name (Optional)</label>
                        <input type="text" class="form-control" id="session_name" name="session_name" 
                               placeholder="e.g., Morning Attendance - Aug 1, 2025">
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This will create or retrieve today's permanent attendance link.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="createSession()">
                    <i class="fas fa-plus me-1"></i>Create Session
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Session Created Success Modal -->
<div class="modal fade" id="sessionCreatedModal" tabindex="-1" aria-labelledby="sessionCreatedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="sessionCreatedModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Session Created Successfully!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="sessionDetails"></div>
                
                <div class="mt-3">
                    <label class="form-label"><strong>Public Attendance Link:</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="publicUrl" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Share this link with students or open it on any device. 
                    The link will expire automatically after the set duration.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="openPublicLink()">
                    <i class="fas fa-external-link-alt me-1"></i>Open Attendance Page
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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

// Session Management Functions
function createSession() {
    const form = document.getElementById('createSessionForm');
    const formData = new FormData(form);
    
    // Use the correct attendance session route
    fetch('{{ route("teacher.attendance.session.create") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide create modal
            bootstrap.Modal.getInstance(document.getElementById('createSessionModal')).hide();
            
            // Show success modal with details
            document.getElementById('sessionDetails').innerHTML = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-calendar me-2"></i>Session Details:</h6>
                    <p><strong>Name:</strong> ${data.session.name || 'Default Session'}</p>
                    <p><strong>Date:</strong> ${data.session.date}</p>
                    <p><strong>Time Created:</strong> ${data.session.created_at}</p>
                    <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                </div>
            `;
            
            document.getElementById('publicUrl').value = data.public_url;
            
            bootstrap.Modal.getOrCreateInstance(document.getElementById('sessionCreatedModal')).show();
            
            // Optionally refresh page after delay
            setTimeout(() => {
                location.reload();
            }, 3000);
        } else {
            showError(data.message || 'Failed to create session');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while creating the session');
    });
}

function showCreateSessionModal() {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('createSessionModal')).show();
}

function copyToClipboard() {
    const urlInput = document.getElementById('publicUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Provide visual feedback
        const copyBtn = event.target.closest('button');
        const originalHtml = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        copyBtn.classList.remove('btn-outline-secondary');
        copyBtn.classList.add('btn-success');
        
        setTimeout(() => {
            copyBtn.innerHTML = originalHtml;
            copyBtn.classList.remove('btn-success');
            copyBtn.classList.add('btn-outline-secondary');
        }, 2000);
        
    } catch (err) {
        console.error('Failed to copy: ', err);
        showError('Failed to copy link to clipboard');
    }
}

function openPublicLink() {
    const url = document.getElementById('publicUrl').value;
    if (url) {
        window.open(url, '_blank');
    }
}

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
