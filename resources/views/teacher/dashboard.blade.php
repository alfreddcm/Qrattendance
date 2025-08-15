@extends('teacher/sidebar')
@section('title', 'Dashboard')
@section('content')

<style>
.compact-card {
    height: 100px !important;
    min-height: 100px !important;
}

.compact-card .card-body {
    padding: 12px !important;
}

.fs-7 {
    font-size: 0.8rem !important;
}

.fs-8 {
    font-size: 0.7rem !important;
}

.table-compact {
    font-size: 0.8rem;
}

.table-compact th,
.table-compact td {
    padding: 0.4rem !important;
}

.btn-compact {
    padding: 0.2rem 0.4rem !important;
    font-size: 0.7rem !important;
}

.card-compact {
    margin-bottom: 0.75rem !important;
}

.container-fluid {
    padding: 0.75rem !important;
}

.school-logo-circle {
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.school-logo-circle:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.school-logo-img {
    transition: all 0.3s ease;
}

.school-logo-img:hover {
    transform: scale(1.1);
}

/* Responsive adjustments for logo */
@media (max-width: 768px) {
    .school-logo-circle {
        width: 60px !important;
        height: 60px !important;
        top: 10px !important;
        right: 10px !important;
    }
    
    .school-logo-circle i {
        font-size: 1.5rem !important;
    }
    
    .col-md-6[style*="padding-right"] {
        padding-right: 80px !important;
    }
}

@media (max-width: 576px) {
    .school-logo-circle {
        width: 50px !important;
        height: 50px !important;
    }
    
    .school-logo-circle i {
        font-size: 1.2rem !important;
    }
    
    .col-md-6[style*="padding-right"] {
        padding-right: 60px !important;
    }
}
</style>

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
        'am_time_in_start_display' => $isActiveSemester ? ($currentSemester->am_time_in_start_display ?? '7:00 AM') : 'N/A',
        'am_time_in_end_display' => $isActiveSemester ? ($currentSemester->am_time_in_end_display ?? '7:30 AM') : 'N/A',
        'pm_time_out_start_display' => $isActiveSemester ? ($currentSemester->pm_time_out_start_display ?? '4:30 PM') : 'N/A',
        'pm_time_out_end_display' => $isActiveSemester ? ($currentSemester->pm_time_out_end_display ?? '5:00 PM') : 'N/A',
        'student_count' => $isActiveSemester ? ($studentCount ?? 0) : 0,
        'present_count' => $isActiveSemester ? ($presentCount ?? 0) : 0,
        'absent_count' => $isActiveSemester ? ($absentCount ?? 0) : 0,
        'incomplete_profiles_count' => $isActiveSemester ? (isset($studentsWithMissingInfo) ? $studentsWithMissingInfo->count() : 0) : 0,
        'most_punctual' => $isActiveSemester ? ($mostPunctual ?? null) : null,
        'most_absent' => $isActiveSemester ? ($mostAbsent ?? null) : null,
        'students_with_missing_info' => $isActiveSemester ? ($studentsWithMissingInfo ?? collect()) : collect(),
    ];
@endphp

<title>@yield('title')</title>

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>
                <span class="me-2">🏠</span>
                Dashboard
            </h2>
            <p class="subtitle">Welcome back! Here's your attendance overview</p>
        </div>
        <div class="page-actions">

        </div>
    </div>
    
</div>

<div class="container">
     @if($semesters->count() > 0)
    <div class="row g-3 mb-3">
         <div class="col-12">
            <div class="card shadow-sm" style="background: linear-gradient(135deg, #4776e6 0%, #8e54e9 100%); position: relative; overflow: hidden;">
               
                
                <div class="card-body text-white p-3">
                    <div class="row align-items-center">
                        <div class="col-md-6" style="padding-right: 100px;">
                            <h5 class="card-title text-white mb-2 d-flex align-items-center">
                                {{ $displayData['semester_name'] }}
                                <a href="{{ route('teacher.semesters') }}" class="btn btn-light btn-sm ms-2">
                                    <i class="fas fa-edit me-1"></i>Update Semester
                                </a>
                            </h5>
                                <div class="col">
                                    <p class="mb-1"><strong>School:</strong> {{ $displayData['school_name'] }}</p>
                                    <p class="mb-0"><strong>Year:</strong> {{ $displayData['school_year'] }}</p>
                                    <p class="mb-0"><strong>Period:</strong> {{ $displayData['date_range'] }}</p>
                                  <p class="mb-1"><strong>AM Time In:</strong> 
                                        <span class="badge bg-success">{{ $displayData['am_time_in_start_display'] }} - {{ $displayData['am_time_in_end_display'] }}</span>
                                    </p>
                                    <p class="mb-1"><strong>PM Time Out:</strong> 
                                        <span class="badge bg-warning">{{ $displayData['pm_time_out_start_display'] }} - {{ $displayData['pm_time_out_end_display'] }}</span>
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
                                                    <td class=" text-dark">
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

    <div class="row g-3 mb-3">
         <div class="col-lg-3 col-md-6">
            <div class="card text-center shadow-sm border-primary compact-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title text-primary mb-0 fs-7">Total Students</h6>
                        <i class="fas fa-users text-primary fs-5"></i>
                    </div>
                    <h3 class="text-primary mb-1">{{ $displayData['student_count'] }}</h3>
                    <small class="text-muted fs-8">{{ $isActiveSemester ? 'Enrolled this semester' : 'No active semester' }}</small>
                </div>
            </div>
        </div>
        
         <div class="col-lg-3 col-md-6">
            <div class="card text-center shadow-sm border-success compact-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title text-success mb-0 fs-7">Present Today</h6>
                        <i class="fas fa-check-circle text-success fs-5"></i>
                    </div>
                    <h3 class="text-success mb-1">{{ $displayData['present_count'] }}</h3>
                    <small class="text-muted fs-8">{{ $isActiveSemester ? 'Students attended' : 'No active semester' }}</small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card text-center shadow-sm border-danger compact-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title text-danger mb-0 fs-7">Absent Today</h6>
                        <i class="fas fa-times-circle text-danger fs-5"></i>
                    </div>
                    <h3 class="text-danger mb-1">{{ $displayData['absent_count'] }}</h3>
                    <small class="text-muted fs-8">{{ $isActiveSemester ? 'Students missing' : 'No active semester' }}</small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card text-center shadow-sm border-warning compact-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title text-warning mb-0 fs-7">Incomplete Profiles</h6>
                        <i class="fas fa-exclamation-triangle text-warning fs-5"></i>
                    </div>
                    <h3 class="text-warning mb-1">{{ $displayData['incomplete_profiles_count'] }}</h3>
                    <small class="text-muted fs-8">{{ $isActiveSemester ? 'Need updates' : 'No active semester' }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Insight Cards -->
    <div class="row g-3 mb-3">
        <!-- Most Punctual Student Card -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-success compact-card">
                <div class="card-header bg-light border-success py-2">
                    <h6 class="mb-0 text-success fs-7">
                        <i class="fas fa-trophy me-2"></i>Most Punctual Student
                    </h6>
                </div>
                <div class="card-body text-center p-3">
                    @if($isActiveSemester && $displayData['most_punctual'])
                        <h6 class="text-success mb-1">{{ $displayData['most_punctual']->name }}</h6>
                        <small class="text-muted fs-8">{{ $displayData['most_punctual']->punctuality_rate ?? 0 }}% attendance rate</small>
                    @else
                        <h6 class="text-muted mb-1">{{ $isActiveSemester ? 'No data available' : 'No active semester' }}</h6>
                        <small class="text-muted fs-8">{{ $isActiveSemester ? 'Attendance data needed' : 'Current date outside semester range' }}</small>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Most Absent Student Card -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-danger compact-card">
                <div class="card-header bg-light border-danger py-2">
                    <h6 class="mb-0 text-danger fs-7">
                        <i class="fas fa-exclamation-circle me-2"></i>Most Absent Student
                    </h6>
                </div>
                <div class="card-body text-center p-3">
                    @if($isActiveSemester && $displayData['most_absent'])
                        <h6 class="text-danger mb-1">{{ $displayData['most_absent']->name }}</h6>
                        <span class="badge bg-danger fs-8">{{ $displayData['most_absent']->absence_count ?? 0 }} days</span>
                    @else
                        <h6 class="text-muted mb-1">{{ $isActiveSemester ? 'No data available' : 'No active semester' }}</h6>
                        <small class="text-muted fs-8">{{ $isActiveSemester ? 'Attendance data needed' : 'Current date outside semester range' }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

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
                                        <th style="width: 150px;">Name</th>
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
                                            <div class="text-truncate" style="max-width: 200px;">{{ $student->name }}</div>
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
                                               class="btn btn-compact btn-primary" title="Update student information">
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

 <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    
    .collapse-toggle:hover {
        background-color: rgba(255,255,255,0.2);
    }
    
    .badge {
        font-weight: 500;
        letter-spacing: 0.025em;
    }
    
    .table thead th {
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .missing-info-badges .badge {
            font-size: 0.6em;
        }
        
        .display-4 {
            font-size: 2rem;
        }
        
        .performance-card {
            min-height: 140px;
        }
    }
    
    .session-time-card {
        transition: all 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .session-time-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }
    
    .session-time-card .card-header {
        padding: 1rem 1.25rem 0.75rem;
    }
    
    .session-time-card .card-body {
        padding: 1rem 1.25rem 1.25rem;
    }
    
    .time-period-badge {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        border-radius: 20px;
        padding: 0.375rem 0.875rem;
    }
    
    .form-control-sm {
        font-size: 0.875rem;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .form-control-sm:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        transform: scale(1.02);
    }
    
    .session-icon {
        font-size: 1.1rem;
        margin-right: 0.5rem;
    }
    
    /* Badge colors for different periods */
    .bg-primary-subtle {
        background-color: rgba(13, 110, 253, 0.1) !important;
        color: #0d6efd !important;
    }
    
    .bg-success-subtle {
        background-color: rgba(25, 135, 84, 0.1) !important;
        color: #198754 !important;
    }
    
    .bg-warning-subtle {
        background-color: rgba(255, 193, 7, 0.1) !important;
        color: #ffc107 !important;
    }
    
    .bg-danger-subtle {
        background-color: rgba(220, 53, 69, 0.1) !important;
        color: #dc3545 !important;
    }
    
    /* Responsive adjustments for cards */
    @media (max-width: 768px) {
        .session-time-card .card-header,
        .session-time-card .card-body {
            padding: 0.875rem 1rem;
        }
        
        .session-time-card h6 {
            font-size: 0.95rem;
        }
        
        .time-period-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.625rem;
        }
    }
    
    .modal-body .small {
        font-size: 0.8em;
        margin-top: 0.25rem;
    }
    
    /* Session actions button styling */
    .session-actions .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
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
                    
                    <div class="mb-3">
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

<!-- Active Sessions Modal -->
<div class="modal fade" id="activeSessionsModal" tabindex="-1" aria-labelledby="activeSessionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activeSessionsModalLabel">
                    <i class="fas fa-list me-2"></i>Active Attendance Sessions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="activeSessionsContainer">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading active sessions...</p>
                    </div>
                </div>
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

<script>
let currentPublicUrl = '';

function showCreateSessionModal() {
    @if(!$isActiveSemester)
        alert('Please add an active semester first.');
        return;
    @endif
    
    const modal = new bootstrap.Modal(document.getElementById('createSessionModal'));
    modal.show();
}

function showActiveSessionsModal() {
    const modal = new bootstrap.Modal(document.getElementById('activeSessionsModal'));
    modal.show();
    loadActiveSessions();
}

async function createSession() {
    const form = document.getElementById('createSessionForm');
    const formData = new FormData(form);
    
    // Get CSRF token from meta tag or form
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                     document.querySelector('input[name="_token"]')?.value;
    
    try {
        const response = await fetch('/teacher/attendance-session/create', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                semester_id: formData.get('semester_id'),
                session_name: formData.get('session_name')
            })
        });
        
        if (!response.ok) {
            // Try to get the error message from the response
            let errorMessage = `HTTP error! status: ${response.status}`;
            try {
                const errorData = await response.json();
                if (errorData.message) {
                    errorMessage += ` - ${errorData.message}`;
                }
            } catch (e) {
                // If we can't parse the response as JSON, try to get it as text
                try {
                    const errorText = await response.text();
                    errorMessage += ` - ${errorText.substring(0, 200)}`;
                } catch (e2) {
                    // Can't get response content
                }
            }
            throw new Error(errorMessage);
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Close create modal
            bootstrap.Modal.getInstance(document.getElementById('createSessionModal')).hide();
            
            // Show success modal with details
            showSessionCreated(result.session);
        } else {
            console.error('Server error:', result);
            alert('Error: ' + (result.message || 'Unknown error occurred'));
        }
    } catch (error) {
        console.error('Error creating session:', error);
        alert('Failed to create session. Please check the console for details. Error: ' + error.message);
    }
}

function showSessionCreated(session) {
    currentPublicUrl = session.public_url;
    
    document.getElementById('sessionDetails').innerHTML = `
        <div class="row">
            <div class="col-sm-4"><strong>Session Name:</strong></div>
            <div class="col-sm-8">${session.name}</div>
        </div>
        <div class="row">
            <div class="col-sm-4"><strong>Semester:</strong></div>
            <div class="col-sm-8">${session.semester_name}</div>
        </div>
        <div class="row">
            <div class="col-sm-4"><strong>Expires:</strong></div>
            <div class="col-sm-8">${session.expires_at}</div>
        </div>
    `;
    
    document.getElementById('publicUrl').value = session.public_url;
    
    const modal = new bootstrap.Modal(document.getElementById('sessionCreatedModal'));
    modal.show();
}

async function loadActiveSessions() {
    try {
        const response = await fetch('/teacher/attendance-session/active');
        const result = await response.json();
        
        const container = document.getElementById('activeSessionsContainer');
        
        if (result.success && result.sessions.length > 0) {
            container.innerHTML = result.sessions.map(session => `
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">${session.name}</h6>
                        <span class="badge bg-success">Active</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <p class="mb-1"><strong>Semester:</strong> ${session.semester_name}</p>
                                <p class="mb-1"><strong>Created:</strong> ${session.created_at}</p>
                                <p class="mb-1"><strong>Expires:</strong> ${session.expires_at}</p>
                                <p class="mb-0"><strong>Access Count:</strong> ${session.access_count}</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-sm btn-primary mb-2" onclick="copySessionLink('${session.public_url}')">
                                    <i class="fas fa-copy"></i> Copy Link
                                </button>
                                <button class="btn btn-sm btn-success mb-2" onclick="window.open('${session.public_url}', '_blank')">
                                    <i class="fas fa-external-link-alt"></i> Open
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="closeSession(${session.id})">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                    <p>No active attendance sessions</p>
                    <button class="btn btn-success" onclick="showCreateSessionModal()">
                        <i class="fas fa-plus me-1"></i>Create New Session
                    </button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
        document.getElementById('activeSessionsContainer').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Failed to load active sessions.
            </div>
        `;
    }
}

async function closeSession(sessionId) {
    if (!confirm('Are you sure you want to close this session?')) {
        return;
    }
    
    try {
        const response = await fetch(`/teacher/attendance-session/${sessionId}/close`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content || '{{ csrf_token() }}'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reload active sessions
            loadActiveSessions();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error closing session:', error);
        alert('Failed to close session. Please try again.');
    }
}

function copyToClipboard() {
    const urlInput = document.getElementById('publicUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand('copy');
    
    // Show feedback
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
    button.classList.add('btn-success');
    button.classList.remove('btn-outline-secondary');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}

function copySessionLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.classList.add('btn-success');
        button.classList.remove('btn-primary');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-primary');
        }, 2000);
    });
}

function openPublicLink() {
    if (currentPublicUrl) {
        window.open(currentPublicUrl, '_blank');
    }
}

// Load today's session when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Session data is now loaded server-side, no need for AJAX
    
    // Event delegation for copy and close buttons in dashboard
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-action="copy"]')) {
            const url = e.target.closest('[data-action="copy"]').getAttribute('data-url');
            copySessionUrl(url);
        }

        if (e.target.closest('[data-action="close"]')) {
            const sessionId = e.target.closest('[data-action="close"]').getAttribute('data-session-id');
            closeSessionDashboard(sessionId);
        }
    });
});

function copySessionUrl(url) {
    navigator.clipboard.writeText(url).then(function() {
        // Show success feedback on button
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.remove('btn-outline-dark');
        btn.classList.add('btn-success');

        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-dark');
        }, 1500);

        // Show success modal instead of alert
        showSuccessModal('URL Copied', '<p class="mb-0"><i class="fas fa-check-circle me-2"></i>Session URL copied to clipboard successfully!</p>');
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
        // Show error modal instead of alert with input field
        showErrorModal('Copy Failed', 
            `<p class="mb-2">Failed to copy URL automatically. Please copy manually:</p>
            <div class="input-group">
                <input type="text" class="form-control" value="${url}" readonly onclick="this.select()">
                <button class="btn btn-outline-primary" type="button" onclick="this.previousElementSibling.select();document.execCommand('copy');">
                    <i class="fas fa-copy"></i>
                </button>
            </div>`
        );
    });
}

function closeSessionDashboard(sessionId) {
    showConfirmModal(
        'Close Session',
        '<p class="mb-0">Are you sure you want to close this session? This action cannot be undone.</p>',
        function() {
            // Execute the close action
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            fetch(`/teacher/attendance-session/${sessionId}/close`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content || '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal('Session Closed', '<p class="mb-0"><i class="fas fa-check-circle me-2"></i>Session closed successfully!</p>');
                    // Reload page after modal is closed
                    document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                        location.reload();
                    }, { once: true });
                } else {
                    showErrorModal('Close Failed', '<p class="mb-0">' + (data.message || 'Failed to close session') + '</p>');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Error', '<p class="mb-0">An error occurred while closing the session. Please try again.</p>');
            })
            .finally(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        }
    );
}

// Modal helper functions
function showSuccessModal(title, message) {
    document.getElementById('successModalLabel').innerHTML = `<i class="fas fa-check-circle me-2"></i>${title}`;
    document.getElementById('successModalBody').innerHTML = message;
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
}

function showErrorModal(title, message) {
    document.getElementById('errorModalLabel').innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${title}`;
    document.getElementById('errorModalBody').innerHTML = message;
    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
    modal.show();
}

function showConfirmModal(title, message, onConfirm) {
    document.getElementById('confirmModalLabel').innerHTML = `<i class="fas fa-question-circle me-2"></i>${title}`;
    document.getElementById('confirmModalBody').innerHTML = message;

    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const confirmBtn = document.getElementById('confirmModalAction');

    // Remove any existing event listeners
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    const newConfirmBtn = document.getElementById('confirmModalAction');

    newConfirmBtn.addEventListener('click', function() {
        modal.hide();
        if (onConfirm) onConfirm();
    });

    modal.show();
}

// Add CSRF token meta tag if not exists
if (!document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = '{{ csrf_token() }}';
    document.getElementsByTagName('head')[0].appendChild(meta);
}
</script>

@endsection
