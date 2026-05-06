@extends('teacher/sidebar')
@section('title', 'QR Scanner')
@section('content')

@php
use Carbon\Carbon;

$schoolYear = \App\Models\SchoolYear::where('status', 'active')->first();
$school = $schoolYear->school ?? null;
$now = Carbon::now('Asia/Manila');

// Define time periods for attendance tracking
$timeSchedules = [];
if ($schoolYear) {
    // AM Time In
    if ($schoolYear->am_time_in_start && $schoolYear->am_time_in_end) {
        $timeSchedules[] = [
            'type' => 'am_time_in',
            'label' => 'AM Time In',
            'start' => Carbon::createFromFormat('H:i:s', $schoolYear->am_time_in_start)->format('g:i A'),
            'end' => Carbon::createFromFormat('H:i:s', $schoolYear->am_time_in_end)->format('g:i A'),
            'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->am_time_in_start),
            'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->am_time_in_end)
        ];
    }

    // AM Time Out
    if ($schoolYear->am_time_out_start && $schoolYear->am_time_out_end) {
        $timeSchedules[] = [
            'type' => 'am_time_out',
            'label' => 'AM Time Out',
            'start' => Carbon::createFromFormat('H:i:s', $schoolYear->am_time_out_start)->format('g:i A'),
            'end' => Carbon::createFromFormat('H:i:s', $schoolYear->am_time_out_end)->format('g:i A'),
            'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->am_time_out_start),
            'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->am_time_out_end)
        ];
    }

    // PM Time In
    if ($schoolYear->pm_time_in_start && $schoolYear->pm_time_in_end) {
        $timeSchedules[] = [
            'type' => 'pm_time_in',
            'label' => 'PM Time In',
            'start' => Carbon::createFromFormat('H:i:s', $schoolYear->pm_time_in_start)->format('g:i A'),
            'end' => Carbon::createFromFormat('H:i:s', $schoolYear->pm_time_in_end)->format('g:i A'),
            'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->pm_time_in_start),
            'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->pm_time_in_end)
        ];
    }

    // PM Time Out
    if ($schoolYear->pm_time_out_start && $schoolYear->pm_time_out_end) {
        $timeSchedules[] = [
            'type' => 'pm_time_out',
            'label' => 'PM Time Out',
            'start' => Carbon::createFromFormat('H:i:s', $schoolYear->pm_time_out_start)->format('g:i A'),
            'end' => Carbon::createFromFormat('H:i:s', $schoolYear->pm_time_out_end)->format('g:i A'),
            'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->pm_time_out_start),
            'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->pm_time_out_end)
        ];
    }
}

// Calculate attendance totals for today
$todayAttendance = \App\Models\Attendance::with('student')
    ->whereDate('created_at', Carbon::today('Asia/Manila'))
    ->where(function($query) {
        $query->whereHas('student', function($q) {
            $q->where('user_id', Auth::id());
        });
    })
    ->get();

// Count attendance by periods
$attendanceCounts = [
    'am_in' => 0,
    'am_out' => 0,
    'pm_in' => 0,
    'pm_out' => 0
];

foreach ($todayAttendance as $record) {
    $recordTime = $record->time_in ?: $record->time_out;
    if (!$recordTime) continue;

    foreach ($timeSchedules as $schedule) {
        if ($recordTime->between($schedule['start_time'], $schedule['end_time'])) {
            switch ($schedule['type']) {
                case 'am_time_in':
                    $attendanceCounts['am_in']++;
                    break;
                case 'am_time_out':
                    $attendanceCounts['am_out']++;
                    break;
                case 'pm_time_in':
                    $attendanceCounts['pm_in']++;
                    break;
                case 'pm_time_out':
                    $attendanceCounts['pm_out']++;
                    break;
            }
            break;
        }
    }
}

// Get recent attendance records with interpretation
$recentAttendance = collect();
$recentRecords = \App\Models\Attendance::with('student')
    ->whereDate('created_at', Carbon::today('Asia/Manila'))
    ->whereHas('student', function($q) {
        $q->where('user_id', Auth::id());
    })
    ->latest()
    ->take(8)
    ->get();

foreach ($recentRecords as $record) {
    $recordTime = $record->time_in ?: $record->time_out;
    $interpretedStatus = 'Unknown';

    if ($recordTime) {
        foreach ($timeSchedules as $schedule) {
            if ($recordTime->between($schedule['start_time'], $schedule['end_time'])) {
                $interpretedStatus = $schedule['label'] . ' Recorded';
                break;
            }
        }
    }

    $record->interpreted_status = $interpretedStatus;
    $recentAttendance->push($record);
}
@endphp

<style>
    :root {
        --primary-color: #2563eb;
        --secondary-color: #f59e0b;
        --success-color: #10b981;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --info-color: #3b82f6;
        --light-bg: #f8fafc;
        --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --card-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        color: #1f2937;
    }


    .modern-header {
        background: white;
        box-shadow: var(--card-shadow);
        padding: 1rem 2rem;
        margin-bottom: 2rem;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1400px;
        margin: 0 auto;
    }

    .school-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .school-logo img {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        object-fit: cover;
    }

    .school-logo i {
        font-size: 3rem;
        color: var(--primary-color);
    }

    .school-details h4 {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.25rem;
    }

    .school-details .address {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .system-title {
        text-align: center;
        flex: 1;
        max-width: 600px;
    }

    .system-title h3 {
        color: var(--primary-color);
        font-weight: 600;
        font-size: 1.5rem;
        line-height: 1.3;
    }


    .main-layout {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1rem;
        display: grid;
        grid-template-columns: 1fr 2fr 1fr;
        gap: 2rem;
        align-items: start;
        margin-bottom: 2rem;
    }


    .modern-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .modern-card:hover {
        box-shadow: var(--card-shadow-lg);
    }


    .photo-panel {
        display: flex;
        flex-direction: column;
    }

    .photo-card {
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .large-photo-container {
        width: 100%;
        height: 400px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        color: #9ca3af;
        position: relative;
    }

    .large-photo-container i {
        font-size: 8rem;
        margin-bottom: 1rem;
    }

    .large-photo-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 10px;
    }

    .photo-text {
        font-size: 1.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 2px;
    }


    .student-info-panel {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .student-card {
        overflow: hidden;
    }

    .student-info-header {
        background: white;
        padding: 1.5rem;
    }

    .info-field {
        margin-bottom: 1rem;
    }

    .info-field:last-child {
        margin-bottom: 0;
    }

    .field-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        text-align: center;
        border: 1px solid #e5e7eb;
        padding: 0.5rem;
        background: #f9fafb;
    }

    .field-value {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        text-align: center;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        background: white;
        min-height: 3rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }


    .attendance-status {
        background: var(--primary-color);
        color: white;
        text-align: center;
        padding: 1.5rem;
    }

    .status-text {
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .status-action {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .status-time {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .status-date {
        font-size: 0.875rem;
        opacity: 0.9;
    }


    .attendance-table {
        overflow: hidden;
    }

    .table-header {
        background: var(--primary-color);
        color: white;
        padding: 1rem;
        font-weight: 600;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-rows {
        background: white;
    }

    .table-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .table-row:last-child {
        border-bottom: none;
    }

    .row-label {
        font-weight: 500;
        color: #374151;
    }

    .row-status {
        font-weight: 600;
        color: #6b7280;
    }


    .control-panel {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }


    .clock-card {
        background: var(--primary-color);
        color: white;
        text-align: center;
        padding: 1rem;
    }

    .clock-header {
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        opacity: 0.9;
    }

    .digital-time {
        font-size: 1.5rem;
        font-weight: 700;
        font-family: 'Courier New', monospace;
    }


    .scanner-card {
        padding: 1.5rem;
    }

    .scanner-toggles {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .scanner-btn {
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        background: white;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .scanner-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .scanner-btn:hover {
        border-color: var(--primary-color);
    }

    .scanner-input {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        text-align: center;
        font-weight: 500;
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
        border-color: #007bff;
        background: #f8f9ff;
    }

    .scanner-status {
        text-align: center;
        font-size: 0.875rem;
        color: #6b7280;
    }


    .summary-card {
        background: var(--primary-color);
        color: white;
        overflow: hidden;
    }

    .summary-header {
        padding: 1rem;
        font-weight: 600;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    .summary-stats {
        padding: 1rem;
    }

    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .stat-row:last-child {
        margin-bottom: 0;
    }

    .stat-label {
        font-size: 0.875rem;
        font-weight: 500;
    }

    .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
    }

    .stat-separator {
        height: 1px;
        background: rgba(255,255,255,0.2);
        margin: 0.75rem 0;
    }


    .recent-students {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1rem;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
    }

    .student-tile {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        box-shadow: var(--card-shadow);
    }

    .student-tile.empty {
        background: #f8fafc;
        color: #9ca3af;
    }

    .tile-time {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        background: #f3f4f6;
        border-radius: 4px;
        margin-bottom: 0.5rem;
    }

    .tile-name {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.25rem;
        font-size: 0.875rem;
    }

    .tile-section {
        font-size: 0.75rem;
        color: #6b7280;
    }


    @media (max-width: 1200px) {
        .main-layout {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .header-content {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .system-title {
            max-width: none;
        }

        .recent-students {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .modern-header {
            padding: 1rem;
        }

        .recent-students {
            grid-template-columns: repeat(2, 1fr);
        }

        .large-photo-container {
            height: 250px;
        }

        .large-photo-container i {
            font-size: 4rem;
        }

        .photo-text {
            font-size: 1rem;
        }

        .system-title h3 {
            font-size: 1.25rem;
        }
    }

    @media (max-width: 480px) {
        .recent-students {
            grid-template-columns: 1fr;
        }
    }


    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #f3f4f6;
        border-top: 3px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="container-fluid p-0">

    <header class="modern-header">
        <div class="header-content">
            <div class="school-brand">
                <div class="school-logo">
                    @if($school && $school->logo)
                        <img src="{{ url('/public-storage/' . ltrim($school->logo, '/')) }}" alt="{{ $school->name ?? 'School Logo' }}" onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'fas fa-graduation-cap\'></i>';">
                    @else
                        <i class="fas fa-graduation-cap"></i>
                    @endif
                </div>
                <div class="school-details">
                    <h4>{{ $school->name ?? 'SCHOOL NAME' }}</h4>
                    <div class="address">{{ $school->address ?? 'ADDRESS' }}</div>
                </div>
            </div>
            <div class="system-title">
                <h3>Scan-to-Notify: QR-Based Student Attendance and Parent Notification System</h3>
            </div>
        </div>
    </header>


    <main class="main-layout">

        <aside class="photo-panel">
            <div class="modern-card photo-card">
                <div class="large-photo-container" id="student-photo">
                    <i class="fas fa-user-graduate"></i>
                    <div class="photo-text">PHOTO</div>
                </div>
            </div>
        </aside>


        <section class="student-info-panel">

            <div class="modern-card student-card">
                <div class="student-info-header">
                    <div class="info-field">
                        <div class="field-label">STUDENT NAME</div>
                        <div class="field-value" id="student-name">STUDENT NAME</div>
                    </div>
                    <div class="info-field">
                        <div class="field-label">SECTION</div>
                        <div class="field-value" id="student-section">SECTION</div>
                    </div>
                </div>


                <div class="attendance-status" id="status-card">
                    <div class="status-text">WAITING TO SCAN</div>
                    <div class="status-action">READY</div>
                    <div class="status-time">--:--</div>
                    <div class="status-date">{{ $now->format('F j, Y') }}</div>
                </div>
            </div>


            <div class="modern-card attendance-table">
                <div class="table-header">ATTENDANCE RECORD</div>
                <div class="table-rows">
                    <div class="table-row">
                        <span class="row-label">AM TIME IN</span>
                        <span class="row-status" id="am-in-status">Not Recorded</span>
                    </div>
                    <div class="table-row">
                        <span class="row-label">AM TIME OUT</span>
                        <span class="row-status" id="am-out-status">Not Recorded</span>
                    </div>
                    <div class="table-row">
                        <span class="row-label">PM TIME IN</span>
                        <span class="row-status" id="pm-in-status">Not Recorded</span>
                    </div>
                    <div class="table-row">
                        <span class="row-label">PM TIME OUT</span>
                        <span class="row-status" id="pm-out-status">Not Recorded</span>
                    </div>
                </div>
            </div>
        </section>


        <aside class="control-panel">

            <div class="modern-card clock-card">
                <div class="clock-header">TODAY IS: {{ strtoupper($now->format('D, M j, Y')) }}</div>
                <div class="digital-time" id="current-time">--:--:-- --</div>
            </div>


            <div class="modern-card scanner-card">
                <div class="scanner-toggles">
                    <button class="scanner-btn active" id="usb-toggle">
                        <i class="fas fa-barcode"></i> USB Scanner
                    </button>
                    <button class="scanner-btn" id="webcam-toggle">
                        <i class="fas fa-camera"></i> QR Camera
                    </button>
                </div>


                <div id="usb-scanner-section">
                    <input type="text"
                           id="usb-scanner-input"
                           class="scanner-input"
                           placeholder="Ready to scan..."
                           autocomplete="off">
                    <div class="scanner-status">
                        <i class="fas fa-check-circle text-success"></i>
                        Ready for USB Scanner<br>
                        Point scanner at QR code
                    </div>
                </div>


                <div id="webcam-scanner-section" style="display: none;">
                    <div id="qr-reader" style="width: 100%; height: 200px; border-radius: 8px; overflow: hidden;"></div>
                    <div class="text-center mt-2">
                        <button class="btn btn-danger btn-sm" id="stop-scanning" onclick="stopScanning()">
                            <i class="fas fa-stop"></i> Stop Scanning
                        </button>
                    </div>
                </div>
            </div>


            <div class="modern-card summary-card">
                <div class="summary-header">TODAY'S ATTENDANCE</div>
                <div class="summary-stats">
                    <div class="stat-row">
                        <span class="stat-label">MORNING IN:</span>
                        <span class="stat-value">{{ $attendanceCounts['am_in'] }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">MORNING OUT:</span>
                        <span class="stat-value">{{ $attendanceCounts['am_out'] }}</span>
                    </div>
                    <div class="stat-separator"></div>
                    <div class="stat-row">
                        <span class="stat-label">AFTERNOON IN:</span>
                        <span class="stat-value">{{ $attendanceCounts['pm_in'] }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">AFTERNOON OUT:</span>
                        <span class="stat-value">{{ $attendanceCounts['pm_out'] }}</span>
                    </div>
                </div>
            </div>
        </aside>
    </main>


    <section class="recent-students">
        @forelse($recentAttendance->take(5) as $record)
            <div class="student-tile">
                <div class="tile-time">TIME IN : {{ $record->time_in ? $record->time_in->format('G:i') : ($record->time_out ? $record->time_out->format('G:i') : '7:20') }}</div>
                <div class="tile-name">{{ $record->student->name ?? 'STUDENT NAME' }}</div>
                <div class="tile-section">{{ $record->student->section ?? 'SECTION' }}</div>
            </div>
        @empty
            @for($i = 0; $i < 5; $i++)
                <div class="student-tile empty">
                    <div class="tile-time">TIME IN : --:--</div>
                    <div class="tile-name">STUDENT NAME</div>
                    <div class="tile-section">SECTION</div>
                </div>
            @endfor
        @endforelse
    </section>
</div>

<div class="loading-overlay" id="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    // Global variables
    let html5QrcodeScanner = null;
    let usbScannerTimeout = null;
    let currentStudentId = null;

    // Update digital clock
    function updateDateTime() {
        const now = new Date();
        const timeOptions = {
            timeZone: 'Asia/Manila',
            hour12: true,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };

        document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
    }

    // Update attendance record status based on student scan
    function updateAttendanceRecord(studentId) {
        if (!studentId) return;

        // Reset all statuses first
        document.querySelectorAll('.row-status').forEach(status => {
            status.textContent = 'Not Recorded';
            status.style.color = '#6b7280';
        });

        // Fetch student's attendance records for today and update UI
        fetch(`/api/student-attendance-today/${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.records) {
                    // Update each period based on actual records
                    Object.keys(data.records).forEach(period => {
                        const statusElement = document.getElementById(period + '-status');
                        if (statusElement && data.records[period]) {
                            statusElement.textContent = 'Recorded';
                            statusElement.style.color = '#059669';
                            statusElement.style.fontWeight = '600';
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching attendance records:', error);
            });
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Initialize scanner functionality
        initializeScanner();
    });

    // Scanner initialization
    function initializeScanner() {
        // USB Scanner setup
        const usbInput = document.getElementById('usb-scanner-input');
        if (usbInput) {
            usbInput.addEventListener('input', function(e) {
                const value = e.target.value.trim();
                if (value.length > 5) { // Assuming QR codes are longer than 5 chars
                    processQRCode(value);
                    e.target.value = ''; // Clear input for next scan
                }
            });

            // Keep USB input focused
            usbInput.addEventListener('blur', function() {
                setTimeout(() => this.focus(), 100);
            });

            usbInput.focus();
        }

        // Scanner toggle functionality
        document.getElementById('usb-toggle').addEventListener('click', function() {
            activateUsbScanner();
        });

        document.getElementById('webcam-toggle').addEventListener('click', function() {
            activateWebcamScanner();
        });
    }

    function activateUsbScanner() {
        // Hide webcam section
        document.getElementById('webcam-scanner-section').style.display = 'none';
        document.getElementById('usb-scanner-section').style.display = 'block';

        // Update toggle buttons
        document.getElementById('usb-toggle').classList.add('active');
        document.getElementById('webcam-toggle').classList.remove('active');

        // Stop webcam scanner if running
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().catch(err => console.log('Error stopping webcam:', err));
            html5QrcodeScanner = null;
        }

        // Focus on USB input
        setTimeout(() => {
            document.getElementById('usb-scanner-input').focus();
        }, 100);
    }

    function activateWebcamScanner() {
        // Show webcam section
        document.getElementById('usb-scanner-section').style.display = 'none';
        document.getElementById('webcam-scanner-section').style.display = 'block';

        // Update toggle buttons
        document.getElementById('webcam-toggle').classList.add('active');
        document.getElementById('usb-toggle').classList.remove('active');

        // Initialize webcam scanner
        setTimeout(() => {
            initializeWebcamScanner();
        }, 300);
    }

    function initializeWebcamScanner() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().catch(err => console.log('Error clearing previous scanner:', err));
        }

        function onScanSuccess(decodedText, decodedResult) {
            console.log('Webcam QR detected:', decodedText);
            processQRCode(decodedText);
        }

        function onScanFailure(error) {
            // Silent fail - normal when no QR in frame
        }

        try {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader",
                {
                    fps: 10,
                    qrbox: { width: 200, height: 200 },
                    rememberLastUsedCamera: true,
                    showTorchButtonIfSupported: true,
                    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
                },
                false
            );

            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        } catch (error) {
            console.error('Error initializing webcam scanner:', error);
            alert('Error initializing camera. Please check permissions.');
        }
    }

    function stopScanning() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                html5QrcodeScanner = null;
                console.log('Webcam scanner stopped');
            }).catch(err => {
                console.log('Error stopping scanner:', err);
            });
        }
    }

    function processQRCode(decodedText) {
        // Validate QR code data
        if (!decodedText || decodedText.trim().length === 0) {
            updateStatusCard('ERROR', 'INVALID QR', '--:--', 'Invalid QR Code');
            playNotificationSound(false);
            return;
        }

        // Clean the QR data - remove any whitespace
        const cleanedQRData = decodedText.trim();
        console.log('QR Data received:', cleanedQRData);

        // Basic validation for student QR format (should be like "12345_ABCDEFGHIJ")
        if (!cleanedQRData.includes('_') || cleanedQRData.length < 5) {
            updateStatusCard('ERROR', 'INVALID FORMAT', '--:--', 'Expected: StudentID_Code');
            playNotificationSound(false);
            return;
        }

        // Show processing state
        const studentData = parseQRData(cleanedQRData);
        updateStudentDisplay(studentData);

        // Show loading
        document.getElementById('loading-overlay').style.display = 'flex';

        fetch("{{ route('teacher.qr.verify') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    qr_data: cleanedQRData,
                    scanner_type: document.getElementById('usb-toggle').classList.contains('active') ? '2D Barcode Scanner' : 'Webcam'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update with server data
                    updateStudentDisplayWithServerData(data.student, data);
                    currentStudentId = data.student.id;
                    updateAttendanceRecord(data.student.id);

                    // Update status card with success
                    const now = new Date();
                    const timeStr = now.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });

                    updateStatusCard('ATTENDANCE RECORDED!', data.time_period || 'TIME IN', timeStr, '{{ $now->format('F j, Y') }}');
                    playNotificationSound(true);

                    // Reset after 5 seconds
                    setTimeout(() => {
                        resetStudentDisplay();
                        location.reload(); // Refresh to update counts
                    }, 5000);
                } else {
                    updateStatusCard('ACCESS DENIED', 'ERROR', '--:--', data.message || 'Please try again');
                    playNotificationSound(false);

                    // Reset after 3 seconds for errors
                    setTimeout(() => {
                        resetStudentDisplay();
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                updateStatusCard('SYSTEM ERROR', 'ERROR', '--:--', 'Please try again');
                playNotificationSound(false);

                // Reset after 3 seconds for errors
                setTimeout(() => {
                    resetStudentDisplay();
                }, 3000);
            })
            .finally(() => {
                // Hide loading
                document.getElementById('loading-overlay').style.display = 'none';
            });
    }

    function parseQRData(qrData) {
        // Simple parsing - assumes QR data contains student ID
        if (qrData.includes('_')) {
            const parts = qrData.split('_');
            return {
                id: parts[0],
                name: 'Student ' + parts[0],
                section: 'Loading...'
            };
        }

        // Fallback - treat as simple student ID
        return {
            id: qrData,
            name: 'Student ' + qrData,
            section: 'Loading...'
        };
    }

    function updateStudentDisplay(studentData) {
        // Update student photo (placeholder)
        const photoElement = document.getElementById('student-photo');
        photoElement.innerHTML = `<i class="fas fa-user-graduate"></i><div class="photo-text">PHOTO</div>`;

        // Update student info
        document.getElementById('student-name').textContent = studentData.name;
        document.getElementById('student-section').textContent = studentData.section;

        // Update status card to processing
        updateStatusCard('PROCESSING...', 'SCANNING', '--:--', '{{ $now->format('F j, Y') }}');
    }

    function updateStudentDisplayWithServerData(studentData, result) {
        // Update student photo if available
        const photoElement = document.getElementById('student-photo');
        if (studentData.picture) {
            photoElement.innerHTML = `<img src="{{ url('/public-storage/student_pictures') }}/${studentData.picture}" alt="${studentData.name}">`;
        } else {
            photoElement.innerHTML = `<i class="fas fa-user-graduate"></i><div class="photo-text">PHOTO</div>`;
        }

        // Update student info with server data
        document.getElementById('student-name').textContent = studentData.name;
        document.getElementById('student-section').textContent = studentData.section || 'No Section';
    }

    function updateStatusCard(statusText, actionText, timeText, dateText) {
        const statusCard = document.getElementById('status-card');
        statusCard.innerHTML = `
            <div class="status-text">${statusText}</div>
            <div class="status-action">${actionText}</div>
            <div class="status-time">${timeText}</div>
            <div class="status-date">${dateText}</div>
        `;
    }

    function resetStudentDisplay() {
        // Reset to waiting state
        document.getElementById('student-photo').innerHTML = '<i class="fas fa-user-graduate"></i><div class="photo-text">PHOTO</div>';
        document.getElementById('student-name').textContent = 'STUDENT NAME';
        document.getElementById('student-section').textContent = 'SECTION';

        updateStatusCard('WAITING TO SCAN', 'READY', '--:--', '{{ $now->format('F j, Y') }}');

        currentStudentId = null;
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
</script>

@endsection
