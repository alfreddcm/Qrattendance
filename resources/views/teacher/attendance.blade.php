@extends('teacher/sidebar')
@section('title', 'Attendance')
@section('content')

<style>
/* Table styling improvements */
.table-light {
    background-color: #f8f9fa !important;
    border-bottom: 2px solid #dee2e6;
}

.table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(0, 0, 0, 0.02);
}

.table-hover > tbody > tr:hover > td {
    background-color: rgba(13, 110, 253, 0.04);
}

.sortable {
    transition: all 0.2s ease;
    user-select: none;
}

.sortable:hover {
    background-color: rgba(255, 255, 255, 0.2) !important;
    transform: translateY(-1px);
}

.sortable i {
    transition: transform 0.2s ease;
    opacity: 0.6;
}

.sortable:hover i {
    opacity: 1;
}

.sortable.sort-asc i::before {
    content: "\f0de"; /* fa-sort-up */
}

.sortable.sort-desc i::before {
    content: "\f0dd"; /* fa-sort-down */
}

.sortable.sort-asc i,
.sortable.sort-desc i {
    color: #ffc107;
    opacity: 1;
}

/* Enhanced badge styling */
.badge {
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* Table cell enhancements */
.table td {
    padding: 0.6rem 0.4rem;
    vertical-align: middle;
    border-color: #e9ecef;
}

.table th {
    padding: 0.8rem 0.4rem;
    font-weight: 600;
    font-size: 0.85em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-color: #dee2e6;
}

/* Sticky header enhancement */
.sticky-top {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <span class="me-2">🗓️</span>
                Attendance
            </h4>
            <p class="subtitle fs-6 mb-0">Manage sessions and view daily attendance records</p>
        </div>

    </div>
</div>

<div class="container mt-4">



    <div class="row mb-4">

        <div class="col">
                <div class="row mb-4 text-center">
        <div class="mt-2">
            <button class="btn btn-primary btn-sm px-2 py-1" data-bs-toggle="modal" data-bs-target="#createSessionModal">
                <i class="fas fa-calendar-day me-1"></i>Get Today's Session
            </button>
            <button class="btn btn-success btn-sm px-2 py-1 ms-2" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
                <i class="fas fa-qrcode me-1"></i>QR Scanner
            </button>
        </div>
    </div>
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white p-2">
                    <h6 class="mb-0 fs-6">
                        <i class="fas fa-play-circle me-1"></i>Active Sessions
                        <span class="badge bg-light text-success ms-2 fs-6">{{ count($activeSessions) }}</span>
                    </h6>
                </div>
                <div class="card-body p-2">
                    @if(count($activeSessions) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-dark sticky-top" style="top: 0; z-index: 1;">
                                <tr>
                                    <th class="py-1 fs-6" style="max-width: 150px; white-space: normal; word-break: break-word;">Session</th>
                                    <th class="py-1 fs-6">Date Created</th>
                                    <th class="py-1 fs-6">Scanned</th>
                                    <th class="py-1 fs-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeSessions as $session)
                                <tr>
                                    <td>
                                        <strong>{{ $session->session_name }}</strong>
                                        <br><small
                                            class="text-muted">{{ $session->semester->name ?? 'Unknown' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success" id="created-{{ $session->id }}">
                                            {{ $session->started_at ? $session->started_at->format('M j, Y') : 'Today' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $session->attendance_count }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary"
                                                data-url="{{ $session->getPublicUrl() }}" data-action="copy"
                                                title="Copy URL">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                             <button class="btn btn-outline-danger" data-session-id="{{ $session->id }}"
                                                data-action="close" title="Close Session">
                                                <i class="fas fa-stop"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-3">
                        <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No active sessions</p>
                        <small class="text-muted">Click "Get Today's Session" to create one</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Sessions -->
        <div class="col-md-6">
            <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                <i class="fas fa-history me-2"></i>Recent Sessions
                <span class="badge bg-light text-secondary ms-2">{{ count($recentSessions) }}</span>
                </h5>
            </div>
            <div class="card-body">
                @if(count($recentSessions) > 0)
                <div class="table-responsive" style="max-height: 192px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th style="max-width: 120px; white-space: normal; word-break: break-word;">Session</th>
                        <th>Duration</th>
                        <th>Scanned</th>
                        <th>Date & Time</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($recentSessions as $session)
                    <tr>
                        <td style="max-width: 120px; white-space: normal; word-break: break-word;">
                        <strong>{{ $session->session_name }}</strong>
                        <br><small
                            class="text-muted">{{ $session->semester->name ?? 'Unknown' }}</small>
                        </td>
                        <td>
                        <span class="badge bg-info">{{ $session->formatted_duration }}</span>
                        </td>
                        <td>
                        <span class="badge bg-primary">{{ $session->attendance_count }}</span>
                        </td>
                        <td>
                        <small class="text-muted">
                            {{ $session->created_at->format('M j, Y') }}<br>
                            {{ $session->created_at->format('g:i A') }}
                        </small>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
                @else
                <div class="text-center py-3">
                <i class="fas fa-history fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No recent sessions</p>
                </div>
                @endif
            </div>
            </div>
        </div>
    </div>

    <!-- Create Session Modal -->
    <div class="modal fade" id="createSessionModal" tabindex="-1" aria-labelledby="createSessionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createSessionModalLabel">
                        <i class="fas fa-calendar-day me-2"></i>Get Today's Attendance Session
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createSessionForm">
                        <div class="mb-3">
                            <label for="semester_id" class="form-label">Semester</label>
                            <select class="form-select" id="semester_id" name="semester_id" required>
                                <option value="">Select Semester</option>
                                @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}"
                                    {{ $semester->status === 'active' ? 'selected' : '' }}>
                                    {{ $semester->name }}
                                    @if($semester->status === 'active')
                                    <span class="text-success">(Active)</span>
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Daily Session:</strong> This session creates a permanent link for today's date. Each day gets its own unique link that never expires.
                            Students can record attendance during the time periods configured for the selected semester.
                            <div class="mt-2">
                                <small class="text-muted">
                                    <em>Time periods are configured per semester and can be updated in the semester settings.</em>
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="createSessionBtn">
                        <i class="fas fa-calendar-check me-2"></i>Get Today's Session
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Scanner Modal -->
    <div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrScannerModalLabel">
                        <i class="fas fa-qrcode me-2"></i>QR Code Scanner
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Scanner Mode Toggle -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-center">
                                <div class="btn-group" role="group" aria-label="Scanner Mode">
                                    <input type="radio" class="btn-check" name="scannerMode" id="usbMode"
                                        autocomplete="off" checked>
                                    <label class="btn btn-outline-primary" for="usbMode">
                                        <i class="fas fa-barcode me-2"></i>2D Barcode Scanner
                                    </label>

                                    <input type="radio" class="btn-check" name="scannerMode" id="webcamMode"
                                        autocomplete="off">
                                    <label class="btn btn-outline-secondary" for="webcamMode">
                                        <i class="fas fa-camera me-2"></i>Webcam (Secondary)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <!-- USB/2D Barcode Scanner Card -->
                            <div class="card shadow-sm border-primary" id="usb-scanner-card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-barcode me-2"></i>2D Barcode Scanner (Primary)
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-qrcode fa-4x text-primary"></i>
                                    </div>
                                    <h5 class="text-primary">Ready for 2D Barcode Scanner</h5>
                                    <p class="text-muted">Point your 2D barcode scanner at a QR code and scan</p>
                                    <div class="alert alert-info">
                                        <strong>Instructions:</strong><br>
                                        1. Connect your 2D barcode scanner<br>
                                        2. Click in the input field below<br>
                                        3. Scan the student QR code
                                    </div>
                                    <input type="text" id="usb-scanner-input"
                                        class="form-control form-control-lg text-center" placeholder="Ready to scan..."
                                        style="font-size: 1.3rem; border: 3px solid #007bff; background: #f8f9ff;">
                                    <small class="text-success mt-2 d-block">
                                        <i class="fas fa-check-circle me-1"></i>Scanner input will appear here
                                        automatically
                                    </small>
                                </div>
                            </div>

                            <!-- Webcam Scanner Card -->
                            <div class="card shadow-sm border-secondary" id="webcam-scanner-card"
                                style="display: none;">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0"><i class="fas fa-camera me-2"></i>Webcam Scanner (Secondary)</h6>
                                </div>
                                <div class="card-body">
                                    <div id="qr-reader" style="width: 100%;"></div>
                                    <small class="text-muted mt-2 d-block text-center">
                                        Use this option if you don't have a 2D barcode scanner
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Time Period Status Card -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Current Time Period</h6>
                                </div>
                                <div class="card-body">
                                    @php
                                    use Carbon\Carbon;

                                    $semester = \App\Models\Semester::where('status', 'active')->first();
                                    $now = Carbon::now();
                                    $currentTimeDisplay = $now->format('g:i:s A');
                                    $currentPeriod = null;

                                    if ($semester) {
                                    $currentPeriod = $semester->getCurrentActivePeriod();
                                    }
                                    @endphp

                                    <div class="text-center">
                                        @if($semester)
                                        @if($currentPeriod)
                                        <div class="alert alert-success mb-0">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>🟢 Active Period:</strong> {{ $currentPeriod['name'] }}<br>
                                            <small>{{ $currentPeriod['start_formatted'] }} -
                                                {{ $currentPeriod['end_formatted'] }}</small><br>
                                            <small class="text-muted">Current Time: {{ $currentTimeDisplay }}</small>
                                        </div>
                                        @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-clock me-2"></i>
                                            <strong>⏳ No Active Period</strong><br>
                                            <small>Current Time: {{ $currentTimeDisplay }}</small>
                                        </div>
                                        @endif
                                        @else
                                        <div class="alert alert-danger mb-0">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>❌ No Active Semester</strong><br>
                                            <small>Please configure an active semester</small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Scan Results -->
                            <div id="qr-result">
                                <div class="alert alert-info text-center">
                                    <strong>Scan a QR code to record attendance</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

     <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="copyToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
            <div class="toast-body">
                URL copied to clipboard!
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
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



    <!-- Today's Recorded Attendance -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Today's Recorded Attendance
                            <span class="badge bg-light text-primary ms-2">{{ date('M j, Y') }}</span>
                        </h5>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2">Present: {{ $totalPresent }}</span>
                            <span class="badge bg-danger me-2">Absent: {{ $totalAbsent }}</span>
                            <span class="badge bg-info">Total: {{ $totalPresent }}/{{ $totalStudents }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search Section -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <form class="d-flex align-items-center gap-2" method="GET" action="{{ route('teacher.attendance') }}">
                                <input type="text" name="search" class="form-control" placeholder="Search student..."
                                    value="{{ $search ?? '' }}" style="min-width: 200px;">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                
                                <!-- Section Selector -->
                                <select name="section_filter" class="form-select" style="min-width: 150px;" onchange="this.form.submit()">
                                    <option value="">All Sections</option>
                                    @php
                                        $sections = \App\Models\Student::where('user_id', Auth::id())
                                            ->with('section')
                                            ->whereHas('section')
                                            ->get()
                                            ->pluck('section')
                                            ->unique('id')
                                            ->sortBy('name');
                                    @endphp
                                    @foreach($sections as $section)
                                        <option value="{{ $section->name }}" {{ request('section_filter') == $section->name ? 'selected' : '' }}>
                                            {{ $section->name }}
                                        </option>
                                    @endforeach
                                </select>
                                
                                @if(request('search') || request('section_filter'))
                                    <a href="{{ route('teacher.attendance') }}" class="btn btn-outline-secondary" title="Clear filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </form>
                        </div>
                        <div class="col-md-4 text-end">
                            <small class="text-muted">
                                @if(request('search') || request('section_filter'))
                                    Filtered results
                                @endif
                            </small>
                        </div>
                    </div>
                    <!-- Attendance Table -->
                    <div class="table-responsive">
                        <div style="max-height: 420px; overflow-y: auto; width: 100%;">
                            <table class="table table table-striped align-middle bg-white">
                                <thead class="table-light sticky-top" style="top: 0; z-index: 1;">
                                    <tr>
                                        <th style="width: 40px; white-space: nowrap;">#</th>
                                        <th class="text-start sortable" data-sort="name" style="cursor: pointer; min-width: 150px;">
                                            Student Name
                                            <i class="fas fa-sort ms-1" aria-hidden="true"></i>
                                        </th>
                                        <th class="text-center sortable" data-sort="section" style="cursor: pointer; width: 90px; white-space: nowrap;">
                                            Section
                                            <i class="fas fa-sort ms-1" aria-hidden="true"></i>
                                        </th>
                                        <th class="text-center sortable" data-sort="status" style="width: 90px; cursor: pointer; white-space: nowrap;">
                                            Status
                                            <i class="fas fa-sort ms-1" aria-hidden="true"></i>
                                        </th>
                                        <th class="text-center" style="width: 70px; white-space: nowrap;">
                                            <div>AM</div>
                                            <div style="font-size: 0.75em;">IN</div>
                                        </th>
                                        <th class="text-center" style="width: 70px; white-space: nowrap;">
                                            <div>AM</div>
                                            <div style="font-size: 0.75em;">OUT</div>
                                        </th>
                                        <th class="text-center" style="width: 70px; white-space: nowrap;">
                                            <div>PM</div>
                                            <div style="font-size: 0.75em;">IN</div>
                                        </th>
                                        <th class="text-center" style="width: 70px; white-space: nowrap;">
                                            <div>PM</div>
                                            <div style="font-size: 0.75em;">OUT</div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="attendance-table-body">
                                    @forelse($attendanceList as $i => $row)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td class="text-start">{{ $row['student']->name }}</td>
                                        <td class="text-center">
                                            @if($row['student']->section)
                                                <span class="badge bg-primary text-white" style="font-size: 0.7em;">
                                                    {{ $row['student']->section->name }}
                                                </span>
                                            @else
                                                <span class="text-muted" style="font-size: 0.8em;">-</span>
                                            @endif
                                        </td>
                                        
                                        <td class="text-center">
                                            @php
                                                $hasTimeIn = $row['time_in_am'] || $row['time_in_pm'];
                                                $hasTimeOut = $row['time_out_am'] || $row['time_out_pm'];
                                            @endphp
                                            
                                            @if($hasTimeIn && $hasTimeOut)
                                                <span class="badge bg-success" style="font-size: 0.7em;">Present</span>
                                            @elseif($hasTimeIn)
                                                <span class="badge bg-warning text-dark" style="font-size: 0.7em;">Partial</span>
                                            @elseif($hasTimeOut)
                                                <span class="badge bg-secondary" style="font-size: 0.7em;">Time Out Only</span>
                                            @else
                                                <span class="badge bg-danger" style="font-size: 0.7em;">Absent</span>
                                            @endif
                                        </td>
                                        <td class="text-center" style="font-size: 0.8em;">
                                            {{ $row['time_in_am'] ? \Carbon\Carbon::parse($row['time_in_am'])->format('H:i') : '-' }}
                                        </td>
                                        <td class="text-center" style="font-size: 0.8em;">
                                            {{ $row['time_out_am'] ? \Carbon\Carbon::parse($row['time_out_am'])->format('H:i') : '-' }}
                                        </td>
                                        <td class="text-center" style="font-size: 0.8em;">
                                            {{ $row['time_in_pm'] ? \Carbon\Carbon::parse($row['time_in_pm'])->format('H:i') : '-' }}
                                        </td>
                                        <td class="text-center" style="font-size: 0.8em;">
                                            {{ $row['time_out_pm'] ? \Carbon\Carbon::parse($row['time_out_pm'])->format('H:i') : '-' }}
                                        </td>
                                        
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                            No attendance records found for today
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  
    <!-- Include HTML5 QR Code Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
    /* 2D Barcode Scanner specific styles */
    #usb-scanner-input {
        transition: all 0.3s ease;
        font-weight: 600;
    }

    #usb-scanner-input:focus {
        border-color: #0056b3 !important;
        box-shadow: 0 0 0 0.3rem rgba(0, 86, 179, 0.25) !important;
        transform: scale(1.02);
        background: #e3f2fd !important;
    }

    .scanner-card {
        transition: all 0.3s ease;
    }

    .scanner-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Animation for successful scan */
    .scan-success {
        animation: scanSuccess 0.5s ease-in-out;
    }

    @keyframes scanSuccess {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }

    /* QR Reader container styling */
    #qr-reader {
        border: 2px dashed #6c757d;
        border-radius: 8px;
        padding: 10px;
    }

    /* Primary scanner emphasis */
    .border-primary {
        border-width: 2px !important;
    }

    .border-secondary {
        border-width: 1px !important;
    }
    </style>

    <script>
    let html5QrcodeScanner = null;
    let usbScannerBuffer = '';
    let usbScannerTimeout = null;

    function processQRCode(decodedText, scannerType = 'unknown') {
        // Validate QR code data
        if (!decodedText || decodedText.trim().length === 0) {
            document.getElementById('qr-result').innerHTML = `
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>⚠️ Invalid QR Code!</strong><br>
                <small>The QR code appears to be empty or invalid.</small>
            </div>
        `;
            playNotificationSound(false);
            return;
        }

        // Clean the QR data - remove any whitespace
        const cleanedQRData = decodedText.trim();

        // Log the QR data for debugging
        console.log('QR Data received:', cleanedQRData);

        // Basic validation for student QR format (should be like "12345_ABCDEFGHIJ")
        if (!cleanedQRData.includes('_') || cleanedQRData.length < 5) {
            document.getElementById('qr-result').innerHTML = `
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>⚠️ Invalid QR Code Format!</strong><br>
                <small>This QR code is not in the expected student format. Expected format: StudentID_Code</small>
            </div>
        `;
            playNotificationSound(false);
            return;
        }

        fetch("{{ route('teacher.qr.verify') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    qr_data: cleanedQRData,
                    scanner_type: scannerType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Display student info with countdown timer for 5 seconds
                    displayStudentInfoWithTimer(data);
                    playNotificationSound(true);

                    // Refresh the attendance table after 5 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 5000);
                } else {
                    document.getElementById('qr-result').innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="fas fa-times-circle me-2"></i>
                    <h6><strong>❌ Access Denied!</strong></h6>
                    <hr>
                    <p class="mb-0">${data.message}</p>
                </div>
            `;

                    playNotificationSound(false);
                    
                    // Return to ready state after 3 seconds for error messages
                    setTimeout(() => {
                        resetToReadyState();
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('qr-result').innerHTML = `
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <h6><strong>⚠️ Error Occurred!</strong></h6>
                <hr>
                <p class="mb-0">Please try again or contact support if the issue persists.</p>
            </div>
        `;
                playNotificationSound(false);
                
                // Return to ready state after 3 seconds for errors
                setTimeout(() => {
                    resetToReadyState();
                }, 3000);
            });
    }

    // Function to display student info with countdown timer
    function displayStudentInfoWithTimer(data) {
        let timeLeft = 5;
        
        function updateDisplay() {
            document.getElementById('qr-result').innerHTML = `
                <div class="alert alert-success text-center scan-success">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><strong>✅ Attendance Recorded!</strong></h6>
                        <span class="badge bg-warning text-dark fs-6" id="countdown">${timeLeft}s</span>
                    </div>
                    <hr>
                    <div class="row text-start">
                        <div class="col-6"><strong>Name:</strong></div>
                        <div class="col-6">${data.student.name}</div>
                        <div class="col-6"><strong>ID No:</strong></div>
                        <div class="col-6">${data.student.id_no}</div>
                        <div class="col-6"><strong>Section:</strong></div>
                        <div class="col-6">${data.student.section || 'N/A'}</div>
                        <div class="col-6"><strong>Period:</strong></div>
                        <div class="col-6"><span class="badge bg-primary">${data.time_period}</span></div>
                        <div class="col-6"><strong>Status:</strong></div>
                        <div class="col-6"><span class="badge bg-success">${data.status}</span></div>
                        <div class="col-6"><strong>Time:</strong></div>
                        <div class="col-6">${data.recorded_time}</div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Student information will be displayed for ${timeLeft} seconds
                        </small>
                    </div>
                </div>
            `;
        }

        // Initial display
        updateDisplay();

        // Update countdown every second
        const countdownInterval = setInterval(() => {
            timeLeft--;
            if (timeLeft > 0) {
                updateDisplay();
            } else {
                clearInterval(countdownInterval);
                resetToReadyState();
            }
        }, 1000);
    }

    // Function to reset scanner to ready state
    function resetToReadyState() {
        document.getElementById('qr-result').innerHTML = `
            <div class="alert alert-info text-center">
                <i class="fas fa-qrcode me-2"></i>
                <strong>Ready to Scan</strong><br>
                <small>Scan a QR code to record attendance</small>
            </div>
        `;
        
        // Clear USB scanner input
        const usbInput = document.getElementById('usb-scanner-input');
        if (usbInput) {
            usbInput.value = '';
            usbInput.focus();
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        processQRCode(decodedText, 'Webcam');
    }

    function playNotificationSound(success) {
        try {
            const audioContext = new(window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            if (success) {
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
            } else {
                oscillator.frequency.setValueAtTime(300, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(200, audioContext.currentTime + 0.2);
            }

            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + (success ? 0.2 : 0.4));

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + (success ? 0.2 : 0.4));
        } catch (e) {
            // Ignore audio errors
        }
    }

    function initWebcamScanner() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear();
        }
        html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", {
                fps: 10,
                qrbox: 300,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            });
        html5QrcodeScanner.render(onScanSuccess);
    }

    function initUSBScanner() {
        const usbInput = document.getElementById('usb-scanner-input');

        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear();
        }

        usbInput.focus();
        usbInput.addEventListener('input', function(e) {
            const value = e.target.value;

            if (usbScannerTimeout) {
                clearTimeout(usbScannerTimeout);
            }

            usbScannerTimeout = setTimeout(() => {
                if (value.trim().length > 0) {
                    processQRCode(value.trim(), '2D Barcode Scanner');
                    e.target.value = '';
                    e.target.focus();
                }
            }, 100);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('qrScannerModal').addEventListener('shown.bs.modal', function() {
            resetToReadyState();
            setTimeout(() => {
                initUSBScanner();
            }, 500);
        });

        // Create Session Button Handler
        document.getElementById('createSessionBtn').addEventListener('click', function() {
            createSession();
        });

        // Event delegation for copy and close buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-action="copy"]')) {
                const url = e.target.closest('[data-action="copy"]').getAttribute('data-url');
                copySessionUrl(url);
            }

            if (e.target.closest('[data-action="close"]')) {
                const sessionId = e.target.closest('[data-action="close"]').getAttribute(
                    'data-session-id');
                closeSession(sessionId);
            }

            if (e.target.closest('#copyFromInputBtn')) {
                copyFromInput();
            }
        });

        // Scanner mode switching
        const webcamMode = document.getElementById('webcamMode');
        const usbMode = document.getElementById('usbMode');
        const webcamCard = document.getElementById('webcam-scanner-card');
        const usbCard = document.getElementById('usb-scanner-card');

        if (webcamMode && usbMode) {
            webcamMode.addEventListener('change', function() {
                if (this.checked) {
                    webcamCard.style.display = 'block';
                    usbCard.style.display = 'none';
                    setTimeout(() => {
                        initWebcamScanner();
                    }, 300);
                }
            });

            usbMode.addEventListener('change', function() {
                if (this.checked) {
                    webcamCard.style.display = 'none';
                    usbCard.style.display = 'block';
                    setTimeout(() => {
                        initUSBScanner();
                    }, 300);
                }
            });
        }
    });

    // Modal helper functions
    function showSuccessModal(title, message) {
        document.getElementById('successModalLabel').innerHTML = `<i class="fas fa-check-circle me-2"></i>${title}`;
        document.getElementById('successModalBody').innerHTML = message;
        const modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();
    }

    function showErrorModal(title, message) {
        document.getElementById('errorModalLabel').innerHTML =
            `<i class="fas fa-exclamation-triangle me-2"></i>${title}`;
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

    // Session Management Functions

    function createSession() {
        const form = document.getElementById('createSessionForm');
        const formData = new FormData(form);

        // Show loading state
        const createBtn = document.querySelector('#createSessionModal .btn-primary');
        const originalText = createBtn.innerHTML;
        createBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Getting session...';
        createBtn.disabled = true;

        fetch("{{ route('teacher.attendance.session.create') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close create modal first
                    bootstrap.Modal.getInstance(document.getElementById('createSessionModal')).hide();

                     const successMessage = `
                <div class="alert alert-success">
                    <h6><strong>Today's session is ready!</strong></h6>
                    <hr>
                    <div class="row">
                        <div class="col-4"><strong>Session Name:</strong></div>
                        <div class="col-8">${data.session.name}</div>
                         
                        <div class="col-8">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="${data.session.public_url}" id="sessionUrlInput" readonly>
                                <button class="btn btn-outline-primary" type="button" id="copyFromInputBtn">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

                    showSuccessModal('Daily Session Ready', successMessage);

                     document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                        location.reload();
                    }, {
                        once: true
                    });
                } else {
                    showErrorModal('Session Failed', data.message || 'Failed to get today\'s session');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Error', 'An error occurred while getting today\'s session. Please try again.');
            })
            .finally(() => {
                 createBtn.innerHTML = originalText;
                createBtn.disabled = false;
            });
    }

    function copyFromInput() {
        const input = document.getElementById('sessionUrlInput');
        input.select();
        navigator.clipboard.writeText(input.value).then(function() {
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-success');

            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-primary');
            }, 1500);
        });
    }

    function copySessionUrl(url) {
        navigator.clipboard.writeText(url).then(function() {
            // Show success feedback on button
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-success');

            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-primary');
            }, 1500);

            // Show Bootstrap toast for quick notification
            const toast = new bootstrap.Toast(document.getElementById('copyToast'));
            toast.show();
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            showErrorModal('Copy Failed',
                `<p class="mb-0">Failed to copy URL. Please copy manually:</p><div class="mt-2"><input type="text" class="form-control" value="${url}" readonly onclick="this.select()"></div>`
                );
        });
    }

    function closeSession(sessionId) {
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
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccessModal('Session Closed',
                                '<p class="mb-0"><i class="fas fa-check-circle me-2"></i>Session closed successfully!</p>'
                                );
                            // Reload page after modal is closed
                            document.getElementById('successModal').addEventListener('hidden.bs.modal',
                                function() {
                                    location.reload();
                                }, {
                                    once: true
                                });
                        } else {
                            showErrorModal('Close Failed', '<p class="mb-0">' + (data.message ||
                                'Failed to close session') + '</p>');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorModal('Error',
                            '<p class="mb-0">An error occurred while closing the session. Please try again.</p>'
                            );
                    })
                    .finally(() => {
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    });
            }
        );
    }


    function showAlert(type, message) {
        // Create or update an alert element
        let alertContainer = document.getElementById('alert-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'alert-container';
            alertContainer.style.position = 'fixed';
            alertContainer.style.top = '20px';
            alertContainer.style.right = '20px';
            alertContainer.style.zIndex = '9999';
            document.body.appendChild(alertContainer);
        }
        
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${iconClass} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        alertContainer.innerHTML = alertHtml;
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);
    }

    // Table sorting functionality
    function initializeTableSorting() {
        const sortableHeaders = document.querySelectorAll('.sortable');
        
        sortableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const sortColumn = this.dataset.sort;
                const currentSort = this.classList.contains('sort-asc') ? 'asc' : 
                                   this.classList.contains('sort-desc') ? 'desc' : 'none';
                
                // Remove sort classes from all headers
                sortableHeaders.forEach(h => {
                    h.classList.remove('sort-asc', 'sort-desc');
                });
                
                // Determine new sort direction
                let newSort = 'asc';
                if (currentSort === 'asc') {
                    newSort = 'desc';
                } else if (currentSort === 'desc') {
                    newSort = 'asc';
                }
                
                // Add sort class to current header
                this.classList.add(`sort-${newSort}`);
                
                // Sort the table
                sortTable(sortColumn, newSort === 'asc');
            });
        });
    }

    function sortTable(column, ascending = true) {
        const tbody = document.querySelector('.table tbody');
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const sortedRows = rows.sort((a, b) => {
            let aVal = getCellValue(a, column);
            let bVal = getCellValue(b, column);
            
            // Handle different data types
            if (column === 'status') {
                // Sort by status priority: Present > Partial > Time Out Only > Absent
                const statusPriority = {
                    'Present': 4,
                    'Partial': 3,
                    'Time Out Only': 2,
                    'Absent': 1
                };
                aVal = statusPriority[aVal] || 0;
                bVal = statusPriority[bVal] || 0;
            } else {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
            }
            
            if (aVal < bVal) return ascending ? -1 : 1;
            if (aVal > bVal) return ascending ? 1 : -1;
            return 0;
        });
        
        // Re-append sorted rows and update row numbers
        sortedRows.forEach((row, index) => {
            // Update row number
            const rowNumberCell = row.cells[0];
            if (rowNumberCell) {
                rowNumberCell.textContent = index + 1;
            }
            tbody.appendChild(row);
        });
    }

    function getCellValue(row, column) {
        const columnMap = {
            'name': 1,      // Student Name
            'section': 2,   // Section 
            'status': 3     // Status
        };
        
        const columnIndex = columnMap[column];
        if (columnIndex === undefined) return '';
        
        const cell = row.cells[columnIndex];
        if (!cell) return '';
        
        // Handle special cases
        if (column === 'name') {
            // Extract text content from the cell
            return cell.textContent.trim();
        } else if (column === 'section') {
            const badge = cell.querySelector('.badge');
            return badge ? badge.textContent.trim() : cell.textContent.trim();
        } else if (column === 'status') {
            const badge = cell.querySelector('.badge');
            return badge ? badge.textContent.trim() : cell.textContent.trim();
        } else {
            return cell.textContent.trim();
        }
    }
    // Initialize table sorting when document is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeTableSorting();
    });
    </script>

    @endsection