@extends('teacher/sidebar')
@section('title', 'Attendance')
@section('content')

<style>
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
        content: "\f0de";
    }

    .sortable.sort-desc i::before {
        content: "\f0dd";
    }

    .sortable.sort-original i::before {
        content: "\f162";
    }

    .sortable.sort-asc i,
    .sortable.sort-desc i {
        color: #ffc107;
        opacity: 1;
    }

    .sortable.sort-original i {
        color: #28a745;
        opacity: 1;
    }


    .badge {
        font-weight: 500;
        letter-spacing: 0.5px;
    }


    .sort-notification {
        border-radius: 8px;
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        font-size: 0.9rem;
    }


    .sortable {
        user-select: none;
        transition: background-color 0.2s ease;
    }

    .sortable:active {
        background-color: rgba(0,123,255,0.1) !important;
    }


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


    .sticky-top {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-calendar-check me-2"></i>
                Attendance
            </h4>
            <p class="subtitle fs-6 mb-0">Manage sessions and view daily attendance records</p>
        </div>

    </div>
</div>

<div class="container mt-4">


    <div class="row mb-4">
        <div class="col-12">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-qrcode me-2"></i>Attendance Access Code
                    </h5>
                </div>
                <div class="card-body">
                    <div id="noActiveCode" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No active code generated</p>
                            <button type="button" class="btn btn-primary" onclick="generateCode()">
                                <i class="fas fa-plus me-2"></i>Generate Access Code
                            </button>
                        </div>
                    </div>

                    <div id="activeCodeDisplay" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-center p-3 border rounded">
                                    <h6 class="text-muted mb-2">6-Digit Code</h6>
                                    <div class="display-4 font-monospace fw-bold text-primary" id="displayCode">
                                        000000
                                    </div>
                                    <small class="text-muted">Share this code with students</small>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="copyCode()">
                                            <i class="fas fa-copy me-1"></i>Copy Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center p-3 border rounded">
                                    <h6 class="text-muted mb-2">QR Code</h6>
                                    <img id="qrCodeImage" src="" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div class="mb-2">
                                    <small class="text-success">
                                        <i class="fas fa-infinity me-1"></i>
                                        Code will remain active until manually deactivated
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-primary me-2" onclick="printCode()" id="printCodeBtn" title="Print Code (Ctrl+P)">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success me-2" onclick="generateCode()">
                                        <i class="fas fa-sync-alt me-1"></i>Regenerate
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deactivateCode()">
                                        <i class="fas fa-times me-1"></i>Deactivate
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info ms-2" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
                                        <i class="fas fa-qrcode me-1"></i>QR Scanner
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i>
                                    Access attendance at:
                                    <strong><span id="accessUrl"></span></strong>
                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyAccessUrl()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


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

                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Current Time Period</h6>
                                </div>
                                <div class="card-body">
                                    @php
                                    use Carbon\Carbon;

                                    $schoolYear = \App\Models\SchoolYear::where('status', 'active')->first();
                                    $now = Carbon::now();
                                    $currentTimeDisplay = $now->format('g:i:s A');
                                    @endphp

                                    <div class="text-center">
                                        @if($schoolYear)
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            <strong>� Active School Year:</strong> 
                                            @if($schoolYear->school_year_start && $schoolYear->school_year_end)
                                                {{ $schoolYear->school_year_start }}–{{ $schoolYear->school_year_end }}
                                            @else
                                                {{ $schoolYear->name }}
                                            @endif
                                            <br>
                                            <small>{{ \Carbon\Carbon::parse($schoolYear->start_date)->format('M j, Y') }} -
                                                {{ \Carbon\Carbon::parse($schoolYear->end_date)->format('M j, Y') }}</small><br>
                                            <small class="text-muted">Current Time: {{ $currentTimeDisplay }}</small>
                                        </div>
                                        @else
                                        <div class="alert alert-danger mb-0">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>❌ No Active School Year</strong><br>
                                            <small>Please configure an active school year</small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>


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

            </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>


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
                 </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>


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

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmModalAction">Confirm</button>
                </div>
            </div>
        </div>
    </div>


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

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <form class="d-flex align-items-center gap-2" method="GET" action="{{ route('teacher.attendance') }}">
                                <input type="text" name="search" class="form-control" placeholder="Search student..."
                                    value="{{ $search ?? '' }}" style="min-width: 200px;">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>


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

                            </small>
                        </div>
                    </div>

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



    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>

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


    #qr-reader {
        border: 2px dashed #6c757d;
        border-radius: 8px;
        padding: 10px;
    }


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
    let activeCodeId = null;
    let updateInterval = null;

    // Load active code on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadActiveCode();
    });

    function loadActiveCode() {
        fetch('{{ route("teacher.attendance.code.active") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.has_active_code) {
                    displayActiveCode(data.data);
                } else {
                    showNoActiveCode();
                }
            })
            .catch(error => {
                console.error('Error loading active code:', error);
                showNoActiveCode();
            });
    }

    function generateCode() {
        if (confirm('Generate a new attendance access code? This will deactivate any existing code.')) {
            fetch('{{ route("teacher.attendance.code.generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    section_id: null,
                    duration: 15
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayActiveCode(data.data);
                    showAlert('success', 'Access code generated successfully!');
                } else {
                    showAlert('danger', data.message || 'Failed to generate code');
                }
            })
            .catch(error => {
                console.error('Error generating code:', error);
                showAlert('danger', 'An error occurred while generating the code');
            });
        }
    }

    function displayActiveCode(codeData) {
        activeCodeId = codeData.id;

        document.getElementById('displayCode').textContent = codeData.code;

        // Handle QR code image
        const qrImage = document.getElementById('qrCodeImage');
        const qrContainer = qrImage.parentElement;
        
        // Clear any existing error messages
        const existingErrors = qrContainer.querySelectorAll('p.text-danger, p.text-muted');
        existingErrors.forEach(error => error.remove());
        
        if (codeData.qr_code_url) {
            qrImage.style.display = 'block';
            qrImage.src = codeData.qr_code_url;
            qrImage.onerror = function() {
                console.error('Failed to load QR image:', codeData.qr_code_url);
                this.style.display = 'none';
                // Check if error message already exists
                if (!qrContainer.querySelector('p.text-danger.qr-error')) {
                    const errorMsg = document.createElement('p');
                    errorMsg.className = 'text-danger small qr-error';
                    errorMsg.textContent = 'QR code image failed to load';
                    qrContainer.appendChild(errorMsg);
                }
            };
        } else {
            qrImage.style.display = 'none';
            // Check if message already exists
            if (!qrContainer.querySelector('p.text-muted.qr-unavailable')) {
                const unavailableMsg = document.createElement('p');
                unavailableMsg.className = 'text-muted small qr-unavailable';
                unavailableMsg.textContent = 'QR code not available';
                qrContainer.appendChild(unavailableMsg);
            }
        }

        document.getElementById('accessUrl').textContent = codeData.access_url;

        document.getElementById('noActiveCode').style.display = 'none';
        document.getElementById('activeCodeDisplay').style.display = 'block';

        // No need for frequent updates since there's no expiration
        if (updateInterval) clearInterval(updateInterval);
        updateInterval = setInterval(loadActiveCode, 300000); // Check every 5 minutes
    }

    function showNoActiveCode() {
        document.getElementById('noActiveCode').style.display = 'block';
        document.getElementById('activeCodeDisplay').style.display = 'none';

        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    }

    function deactivateCode() {
        if (!activeCodeId) return;

        if (confirm('Are you sure you want to deactivate this code? Students will no longer be able to access attendance with this code.')) {
            fetch(`/teacher/attendance-code/${activeCodeId}/deactivate`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Code deactivated successfully');
                    showNoActiveCode();
                } else {
                    showAlert('danger', data.message || 'Failed to deactivate code');
                }
            })
            .catch(error => {
                console.error('Error deactivating code:', error);
                showAlert('danger', 'An error occurred');
            });
        }
    }

    function copyCode() {
        const code = document.getElementById('displayCode').textContent;
        navigator.clipboard.writeText(code).then(() => {
            showAlert('success', 'Code copied to clipboard!');
        }).catch(err => {
            showAlert('danger', 'Failed to copy code');
        });
    }

    function copyAccessUrl() {
        const url = document.getElementById('accessUrl').textContent;
        navigator.clipboard.writeText(url).then(() => {
            showAlert('success', 'URL copied to clipboard!');
        }).catch(err => {
            showAlert('danger', 'Failed to copy URL');
        });
    }

    function printCode() {
        if (!activeCodeId) {
            showAlert('danger', 'No active code to print');
            return;
        }

        // Open print page in new window
        const printUrl = `/teacher/attendance-code/${activeCodeId}/print`;
        window.open(printUrl, '_blank');
    }

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            <i class="fas fa-${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

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

        console.log('Initializing table sorting, found headers:', sortableHeaders.length);

        sortableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                console.log('Header clicked:', this.dataset.sort);

                const sortColumn = this.dataset.sort;
                const currentSort = this.classList.contains('sort-asc') ? 'asc' :
                                   this.classList.contains('sort-desc') ? 'desc' : 'none';

                // Remove sort classes from all headers
                sortableHeaders.forEach(h => {
                    h.classList.remove('sort-asc', 'sort-desc', 'sort-original');
                    const icon = h.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-sort ms-1';
                    }
                });

                // Determine new sort direction (3-state cycle: none -> asc -> desc -> none)
                let newSort = 'asc';
                if (currentSort === 'none') {
                    newSort = 'asc';
                } else if (currentSort === 'asc') {
                    newSort = 'desc';
                } else if (currentSort === 'desc') {
                    newSort = 'original';
                }

                // Add sort class to current header
                this.classList.add(`sort-${newSort}`);

                // Update icon
                const icon = this.querySelector('i');
                if (icon) {
                    if (newSort === 'asc') {
                        icon.className = 'fas fa-sort-up ms-1';
                        icon.style.color = '#ffc107';
                    } else if (newSort === 'desc') {
                        icon.className = 'fas fa-sort-down ms-1';
                        icon.style.color = '#ffc107';
                    } else if (newSort === 'original') {
                        icon.className = 'fas fa-sort-numeric-up ms-1';
                        icon.style.color = '#28a745';
                    }
                }

                console.log('Sorting by:', sortColumn, 'direction:', newSort);

                // Sort the table
                sortTable(sortColumn, newSort);
            });
        });
    }

    function sortTable(column, sortMode = 'asc') {
        const tbody = document.querySelector('#attendance-table-body');
        if (!tbody) {
            console.log('Could not find table body');
            return;
        }

        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Filter out empty state rows (rows with colspan)
        const dataRows = rows.filter(row => {
            const firstCell = row.cells[0];
            return firstCell && !firstCell.hasAttribute('colspan');
        });

        if (dataRows.length === 0) {
            console.log('No data rows to sort');
            return;
        }

        let sortedRows;

        if (sortMode === 'original') {
            // Sort by original order (by data-original-index or just by current DOM order)
            sortedRows = [...dataRows].sort((a, b) => {
                // Get the original index from data attribute or use current index
                const aIndex = parseInt(a.getAttribute('data-original-index')) || Array.from(tbody.children).indexOf(a);
                const bIndex = parseInt(b.getAttribute('data-original-index')) || Array.from(tbody.children).indexOf(b);
                return aIndex - bIndex;
            });
        } else {
            // Store original indices if not already stored
            dataRows.forEach((row, index) => {
                if (!row.hasAttribute('data-original-index')) {
                    row.setAttribute('data-original-index', index);
                }
            });

            sortedRows = dataRows.sort((a, b) => {
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

                const ascending = sortMode === 'asc';
                if (aVal < bVal) return ascending ? -1 : 1;
                if (aVal > bVal) return ascending ? 1 : -1;
                return 0;
            });
        }

        // Clear the tbody and re-append sorted rows
        tbody.innerHTML = '';

        // Re-append sorted rows and update row numbers
        sortedRows.forEach((row, index) => {
            // Update row number
            const rowNumberCell = row.cells[0];
            if (rowNumberCell) {
                rowNumberCell.textContent = index + 1;
            }
            tbody.appendChild(row);
        });

        // If there were no data rows originally, add the empty state back
        if (rows.length > 0 && dataRows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                        No attendance records found for today
                    </td>
                </tr>
            `;
        }

        // Add visual feedback
        const sortInfo = sortMode === 'original' ? 'Original Order' :
                        sortMode === 'asc' ? 'A → Z' : 'Z → A';
        console.log(`Table sorted: ${column} (${sortInfo})`);

        // Show a temporary sort notification
        showSortNotification(column, sortInfo);
    }

    function showSortNotification(column, sortInfo) {
        // Remove any existing notifications
        const existingNotification = document.querySelector('.sort-notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'sort-notification alert alert-info';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
            min-width: 250px;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;

        const columnDisplayNames = {
            'name': 'Student Name',
            'section': 'Section',
            'status': 'Status'
        };

        notification.innerHTML = `
            <i class="fas fa-sort me-2"></i>
            <strong>Sorted by ${columnDisplayNames[column] || column}:</strong> ${sortInfo}
        `;

        document.body.appendChild(notification);

        // Fade in
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);

        // Fade out and remove after 2 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 2000);
    }

    function getCellValue(row, column) {
        const columnMap = {
            'name': 1,
            'section': 2,
            'status': 3
        };

        const columnIndex = columnMap[column];
        if (columnIndex === undefined) return '';

        const cell = row.cells[columnIndex];
        if (!cell) return '';

         if (column === 'name') {
             return cell.textContent.trim();
        } else if (column === 'section') {
            const badge = cell.querySelector('.badge');
            if (badge) {
                return badge.textContent.trim();
            } else {
                const textContent = cell.textContent.trim();
                 return textContent === '-' || textContent === '' ? 'zzz' : textContent;
            }
        } else if (column === 'status') {
            const badge = cell.querySelector('.badge');
            return badge ? badge.textContent.trim() : cell.textContent.trim();
        } else {
            return cell.textContent.trim();
        }
    }
     document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing table sorting...');
        initializeTableSorting();

         setTimeout(() => {
            console.log('Re-initializing table sorting after timeout...');
            initializeTableSorting();
        }, 1000);
    });
    </script>

    @endsection
