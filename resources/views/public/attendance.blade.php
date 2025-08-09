@php
use Carbon\Carbon;

$semester = $session->semester;
$school = $semester->school ?? null;
$now = Carbon::now('Asia/Manila');

$timeSchedules = [];
if ($semester) {
    if ($semester->am_time_in_start && $semester->am_time_in_end) {
        $timeSchedules[] = [
            'label' => 'AM Period (Time In/Out)',
            'start' => Carbon::createFromFormat('H:i:s', $semester->am_time_in_start)->format('g:i A'),
            'end' => Carbon::createFromFormat('H:i:s', $semester->am_time_in_end)->format('g:i A'),
            'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->am_time_in_start),
            'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->am_time_in_end)
        ];
    }
    if ($semester->pm_time_out_start && $semester->pm_time_out_end) {
        $timeSchedules[] = [
            'label' => 'PM Period (Time In/Out)', 
            'start' => Carbon::createFromFormat('H:i:s', $semester->pm_time_out_start)->format('g:i A'),
            'end' => Carbon::createFromFormat('H:i:s', $semester->pm_time_out_end)->format('g:i A'),
            'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->pm_time_out_start),
            'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->pm_time_out_end)
        ];
    }
}

// Get attendance status
$attendanceStatus = $session->isAttendanceAllowed();
$isWithinAllowedTime = $attendanceStatus['allowed'];

// Get recent attendance records
$recentAttendance = collect();
if (isset($session)) {
    $recentAttendance = \App\Models\Attendance::with('student')
        ->whereDate('created_at', Carbon::today('Asia/Manila'))
        ->latest()
        ->take(5)
        ->get();
}
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/attendance.css') }}" rel="stylesheet">
</head>
<body>
     <div class="compact-header">
        <div class="school-info">
            <div class="school-logo">
                @if($school && $school->logo)
                    <img src="{{ asset('storage/school_logos/' . $school->logo) }}" alt="{{ $school->name ?? 'School Logo' }}" style="max-width: 100%; height: auto;">
                @else
                    <i class="fas fa-graduation-cap fa-3x"></i>
                @endif
            </div>
            <div class="school-details">
                <h5>{{ $school->name ?? 'SCHOOL NAME' }}</h5>
                <small>{{ $school->address ?? 'ADDRESS' }}</small>
            </div>
        </div>
        <div class="system-title">
        <h2>
          Scan-to-Notify: A QR-Based Student Attendance and Parent Notification System
        </h2>   
        </div>
    </div>

    <div class="datetime-bar" id="datetime-bar"></div>

     <div class="main-container">
         <div class="session-panel">
            <div class="panel-header">
                <i class="fas fa-qrcode"></i>
                Scanner & Session
            </div>
            <div class="panel-content">
                 <div class="time-periods">
                    <div style="font-size: 0.7rem; font-weight: 600; margin-bottom: 5px; color: #2196F3;">
                        <i class="fas fa-clock"></i> Today's Periods
                    </div>
                    @forelse($timeSchedules as $schedule)
                        <div class="time-period {{ ($schedule['start_time'] && $schedule['end_time'] && $now->between($schedule['start_time'], $schedule['end_time'])) ? 'active' : 'inactive' }}">
                            <span>{{ $schedule['label'] }}</span>
                            <span>{{ $schedule['start'] }}-{{ $schedule['end'] }}</span>
                        </div>
                    @empty
                        <div class="time-period inactive">
                            <span>No periods set</span>
                        </div>
                    @endforelse
                    
                    @if($isWithinAllowedTime)
                        <div style="margin-top: 8px; color: #4CAF50; font-size: 0.7rem; text-align: center;">
                            <i class="fas fa-check-circle"></i> Currently accepting
                        </div>
                    @else
                        <div style="margin-top: 8px; color: #f44336; font-size: 0.7rem; text-align: center;">
                            <i class="fas fa-times-circle"></i> Outside hours
                        </div>
                    @endif
                </div>

                @if(!$isWithinAllowedTime)
                <div class="outside-hours-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    Outside attendance hours
                    @if(isset($attendanceStatus['next_period']))
                        <br><small>Next: {{ $attendanceStatus['next_period']['period_name'] }} at {{ $attendanceStatus['next_period']['start_time'] }}</small>
                    @endif
                </div>
                @endif

                <!-- Scanner Toggle -->
                <div class="scanner-toggle">
                    <button class="toggle-btn active" id="usb-toggle">
                        <i class="fas fa-barcode"></i> USB Scanner
                    </button>
                    <button class="toggle-btn" id="webcam-toggle">
                        <i class="fas fa-camera"></i> QR Camera
                    </button>
                </div>

                <!-- USB Scanner Input -->
                <div id="usb-scanner-section">
                    <input type="text" 
                           id="usb-scanner-input" 
                           class="usb-scanner-input" 
                           placeholder="Ready to scan..."
                           autocomplete="off">
                    <p class="text-center mt-2 text-muted" style="font-size: 0.7rem;">
                        <i class="fas fa-check-circle text-success"></i>
                        Ready for USB Scanner<br>
                        Point scanner at QR code
                    </p>
                </div>

                <!-- Webcam QR Scanner -->
                <div id="webcam-scanner-section" style="display: none;">
                    <div class="webcam-container">
                        <div class="webcam-header">
                            <i class="fas fa-camera"></i>
                            QR Camera Scanner
                        </div>
                        <div id="qr-reader" style="width: 100%; height: 200px;"></div>
                        <div class="camera-controls">
                            <button class="btn btn-danger btn-sm" id="stop-scanning" onclick="stopScanning()">
                                Stop Scanning
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Middle Panel: Student Display with Material Design -->
        <div class="scanner-panel">
            <div class="scanner-header">
                <div class="status-badge" id="status-badge">WAITING TO SCAN</div>
                <h6 class="scanner-title">Student Attendance</h6>
            </div>

            <div class="scanner-content">
                <!-- Material Design Student Card -->
                <div id="material-student-card" class="material-card-container">
                    <div class="material-card waiting">
                        <div class="card-header">
                            <div class="waiting-indicator">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="card-title">Ready to Scan</div>
                        </div>
                        <div class="card-content">
                            <div class="waiting-message">
                                Point your scanner at a QR code to record attendance
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legacy Student Preview (Hidden for Material Design) -->
                <div class="student-preview" id="student-preview" style="display: none;">
                    <div id="notification-area" class="notification-area" style="display: none;">
                        <div id="notification-content" class="notification-content"></div>
                    </div>
                    
                    <div class="student-photo-display" id="student-photo">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="student-info-display" id="student-info">
                        <div class="student-name">WAITING TO SCAN</div>
                        <div class="student-details">Point your scanner at a QR code</div>
                        <div class="student-details">to record attendance</div>
                    </div>
                </div>

                 <div class="material-info-summary">
                    <div class="info-cards">
                        <div class="info-card-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value" id="name-value">-</span>
                        </div>
                        <div class="info-card-row">
                            <span class="info-label">Section:</span>
                            <span class="info-value" id="section-value">-</span>
                        </div>
                        <div class="info-card-row">
                            <span class="info-label">Time:</span>
                            <span class="info-value" id="time-value">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

         <div class="attendance-panel">
            <div class="card" style="width:100%; margin-bottom: 15px;">
                <div class="card-body">
                    <h5 class="card-title">Teacher Assigned</h5>
                    <h6 class="card-subtitle mb-2 text-muted">{{ $teacher_name }}</h6>
                    
                    <div class="session-item">
                        <div class="session-label">Session</div>
                        <div class="session-value" style="word-wrap: break-word; white-space: normal;">{{ $session->session_name ?? 'N/A' }}</div>
                    </div>
                    
                    <div class="session-item">
                        <div class="session-label">Semester</div>
                        <div class="session-value" style="word-wrap: break-word; white-space: normal;">{{ $semester->name ?? 'Unknown' }}</div>
                    </div>
                </div>
            </div>

            <div class="attendance-header">
                <div class="d-flex align-items-center">
                    <i class="fas fa-list me-1"></i>
                    Last Records Today
                </div>
                <div class="attendance-count" id="attendance-count">
                    {{ $recentAttendance->count() }}
                </div>
            </div>
            
            <div class="attendance-list" id="attendance-list">
                @forelse($recentAttendance->take(5) as $record)
                    <div class="attendance-record">
                        <div class="student-avatar">
                            @if($record->student && $record->student->picture)
                                <img src="{{ asset('storage/student_pictures/' . $record->student->picture) }}" alt="{{ $record->student->name }}">
                            @else
                                {{ $record->student ? substr($record->student->name, 0, 1) : 'S' }}
                            @endif
                        </div>
                        <div class="record-info">
                            <div class="record-name">{{ $record->student->name ?? 'Unknown Student' }}</div>
                            <div class="record-section">{{ $record->student->section ?? 'No Section' }}</div>
                        </div>
                        <div class="record-time">
                            @if($record->time_out)
                                <div class="time-out-badge">
                                    Time Out - {{ $record->time_out->format('g:i A') }}
                                </div>
                            @elseif($record->time_in)
                                <div class="time-in-badge">
                                    Time In - {{ $record->time_in->format('g:i A') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center p-3" style="color: #666; font-size: 0.8rem;">
                        <i class="fas fa-clock"></i><br>
                        No attendance records yet today
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Status Container -->
    <div id="status-container" class="status-container"></div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        // Set session token for JavaScript
        window.sessionToken = '{{ $session->session_token }}';
    </script>
    <script src="{{ asset('js/attendance.js') }}"></script>
</body>
</html>
