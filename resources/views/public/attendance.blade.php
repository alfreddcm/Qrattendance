<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance Scanner - {{ $session->session_name ?? 'Attendance Session' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
 
    @php
    use Carbon\Carbon;

    $now = Carbon::now('Asia/Manila');
    $school = $semester->school ?? null;

    // Define time periods for attendance tracking
    $timeSchedules = [];
    if ($semester) {
        // AM Time In
        if ($semester->am_time_in_start && $semester->am_time_in_end) {
            $timeSchedules[] = [
                'type' => 'am_time_in',
                'label' => 'AM Time In',
                'start' => Carbon::createFromFormat('H:i:s', $semester->am_time_in_start)->format('g:i A'),
                'end' => Carbon::createFromFormat('H:i:s', $semester->am_time_in_end)->format('g:i A'),
                'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->am_time_in_start),
                'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->am_time_in_end)
            ];
        }
        
        // AM Time Out
        if ($semester->am_time_out_start && $semester->am_time_out_end) {
            $timeSchedules[] = [
                'type' => 'am_time_out',
                'label' => 'AM Time Out',
                'start' => Carbon::createFromFormat('H:i:s', $semester->am_time_out_start)->format('g:i A'),
                'end' => Carbon::createFromFormat('H:i:s', $semester->am_time_out_end)->format('g:i A'),
                'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->am_time_out_start),
                'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->am_time_out_end)
            ];
        }
        
        // PM Time In
        if ($semester->pm_time_in_start && $semester->pm_time_in_end) {
            $timeSchedules[] = [
                'type' => 'pm_time_in',
                'label' => 'PM Time In',
                'start' => Carbon::createFromFormat('H:i:s', $semester->pm_time_in_start)->format('g:i A'),
                'end' => Carbon::createFromFormat('H:i:s', $semester->pm_time_in_end)->format('g:i A'),
                'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->pm_time_in_start),
                'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->pm_time_in_end)
            ];
        }
        
        // PM Time Out
        if ($semester->pm_time_out_start && $semester->pm_time_out_end) {
            $timeSchedules[] = [
                'type' => 'pm_time_out',
                'label' => 'PM Time Out', 
                'start' => Carbon::createFromFormat('H:i:s', $semester->pm_time_out_start)->format('g:i A'),
                'end' => Carbon::createFromFormat('H:i:s', $semester->pm_time_out_end)->format('g:i A'),
                'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->pm_time_out_start),
                'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->pm_time_out_end)
            ];
        }
    }

    // Calculate attendance totals for today
    $todayAttendance = \App\Models\Attendance::with('student')
        ->whereDate('created_at', Carbon::today('Asia/Manila'))
        ->whereHas('student', function($query) use ($session) {
            $query->where('user_id', $session->teacher_id)
                  ->where('semester_id', $session->semester_id);
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
    $recentAttendanceProcessed = collect();
    foreach ($recentAttendance->take(8) as $record) {
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
        $recentAttendanceProcessed->push($record);
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
            background-attachment: fixed;
            min-height: 100vh;
            color: #1f2937;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.3) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Header Section */
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
            width: 65px;
            height: 65px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }

        .school-logo i {
            font-size: 3.5rem;
            color: var(--primary-color);
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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

        /* Main Layout */
        .main-layout {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 350px 1fr 300px;
            gap: 1.5rem;
            align-items: start;
            margin-bottom: 1.5rem;
        }

        /* Modern Card Styles */
        .modern-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }

    

        /* Left Panel - Large Photo */
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
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 3px solid #cbd5e1;
            border-radius: 12px;
            color: #94a3b8;
            position: relative;
            overflow: hidden;
        }

        .large-photo-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(203, 213, 225, 0.1) 10px,
                rgba(203, 213, 225, 0.1) 20px
            );
            pointer-events: none;
        }

        .large-photo-container i {
            font-size: 8rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .large-photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .photo-text {
            font-size: 1.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            position: relative;
            z-index: 1;
        }

        /* Center Panel - Student Info */
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
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0;
            text-align: center;
            border: 2px solid #cbd5e1;
            border-bottom: none;
            padding: 0.75rem;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        }

        .field-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            text-align: center;
            padding: 1.25rem;
            border: 2px solid #cbd5e1;
            background: white;
            min-height: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Attendance Status */
        .attendance-status {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            color: white;
            text-align: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .attendance-status::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .status-text {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        .status-action {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        .status-time {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            font-family: 'Courier New', monospace;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        .status-date {
            font-size: 0.875rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        /* Attendance Record Table */
        .attendance-table {
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 1rem;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.875rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            background: white;
            transition: background 0.2s ease;
        }

        .table-row:nth-child(odd) {
            background: #f8fafc;
        }

        .table-row:hover {
            background: #f0f7ff;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .row-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }

        .row-status {
            font-weight: 700;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .row-status.recorded {
            color: #059669;
        }

        /* Right Panel - Controls */
        .control-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Clock Card */
        .clock-card {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            text-align: center;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
        }

        .clock-header {
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            opacity: 0.95;
            letter-spacing: 0.5px;
        }

        .digital-time {
            font-size: 1.75rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* Scanner Card */
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
            border: 2px solid #2563eb;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            background: #f0f7ff;
            color: #1f2937;
            transition: all 0.3s ease;
        }

        .scanner-input:focus {
            outline: none;
            border-color: #1d4ed8;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .scanner-status {
            text-align: center;
            font-size: 0.75rem;
            color: #059669;
            line-height: 1.5;
            padding: 0.5rem;
            background: #f0fdf4;
            border-radius: 6px;
            border: 1px solid #d1fae5;
        }

        .scanner-status i {
            margin-right: 0.25rem;
        }

        /* Summary Card */
        .summary-card {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
        }

        .summary-header {
            padding: 1rem;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            background: rgba(0,0,0,0.1);
            font-size: 0.875rem;
        }

        .summary-stats {
            padding: 1rem;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
        }

        .stat-row:last-child {
            margin-bottom: 0;
        }

        .stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .stat-separator {
            height: 2px;
            background: rgba(255,255,255,0.3);
            margin: 0.75rem 0;
        }

        /* Bottom Panel - Recent Students */
        .recent-students-section {
            max-width: 1400px;
            margin: 0 auto 2rem;
            padding: 0 1rem;
        }

        .recent-students-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            background: transparent;
        }

        .student-tile {
            background: white;
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 0.75rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }

        .student-tile:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow-lg);
        }

        .student-tile.empty {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            color: #9ca3af;
        }

        .tile-time {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.35rem 0.5rem;
            background: #2563eb;
            color: white;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .student-tile.empty .tile-time {
            background: #cbd5e1;
            color: #6b7280;
        }

        .tile-name {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .student-tile.empty .tile-name {
            color: #9ca3af;
        }

        .tile-section {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 600;
        }

        .student-tile.empty .tile-section {
            color: #cbd5e1;
        }

        /* Responsive Design */
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

            .recent-students-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .modern-header {
                padding: 1rem;
            }
            
            .recent-students-container {
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
            .recent-students-container {
                grid-template-columns: 1fr;
            }
        }

        /* Loading States */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            flex-direction: column;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 1rem;
        }

        .loading-overlay::after {
            content: 'Processing...';
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <!-- Header Section -->
        <header class="modern-header">
            <div class="header-content">
                <div class="school-brand">
                    <div class="school-logo">
                        @if($school && $school->logo)
                            <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name ?? 'School Logo' }}" onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'fas fa-graduation-cap\'></i>';">
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

                <!-- <section class="recent-students">
            @forelse($recentAttendanceProcessed->take(5) as $record)
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
        </section> -->

            </aside>

            <!-- Center Panel: Student Information & Attendance Records -->
            <section class="student-info-panel">
                <!-- Student Name and Section -->
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

            <!-- Right Panel: Clock, Scanner & Summary -->
            <aside class="control-panel">
                <!-- Digital Clock -->
                <div class="modern-card clock-card">
                    <div class="clock-header">TODAY IS: {{ strtoupper($now->format('D, M j, Y')) }}</div>
                    <div class="digital-time" id="current-time">--:--:-- --</div>
                </div>

                <!-- Scanner Controls -->
                <div class="modern-card scanner-card">
                    <div class="scanner-toggles">
                        <button class="scanner-btn active" id="usb-toggle">
                            <i class="fas fa-barcode"></i> USB Scanner
                        </button>
                        <button class="scanner-btn" id="webcam-toggle">
                            <i class="fas fa-camera"></i> QR Camera
                        </button>
                    </div>

                    <!-- USB Scanner Input -->
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

                    <!-- Webcam QR Scanner -->
                    <div id="webcam-scanner-section" style="display: none;">
                        <div id="qr-reader" style="width: 100%; height: 200px; border-radius: 8px; overflow: hidden;"></div>
                        <div class="text-center mt-2">
                            <button class="btn btn-danger btn-sm" id="stop-scanning" onclick="stopScanning()">
                                <i class="fas fa-stop"></i> Stop Scanning
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Today's Attendance Summary -->
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

        <!-- Bottom Panel: Recent Students -->
        <section class="recent-students-section">
            <div class="recent-students-container" id="recent-students-container">
                @forelse($recentAttendanceProcessed->take(5) as $record)
                    <div class="student-tile">
                        <div class="tile-time">TIME IN : {{ $record->time_in ? $record->time_in->format('G:i') : ($record->time_out ? $record->time_out->format('G:i') : '--:--') }}</div>
                        <div class="tile-name">{{ $record->student->name ?? 'STUDENT NAME' }}</div>
                        <div class="tile-section">{{ $record->student->section->name ?? 'SECTION' }}</div>
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
            </div>
        </section>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Session Data for JavaScript -->
    <script>
        window.sessionData = {
            token: '{{ $session->session_token }}',
            sessionId: {{ $session->id }},
            timeSchedules: @json($timeSchedules),
            csrfToken: '{{ csrf_token() }}'
        };
    </script>

    <!-- Scripts -->
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
                status.classList.remove('recorded');
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
                                statusElement.textContent = data.records[period];
                                statusElement.classList.add('recorded');
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

         function initializeScanner() {
             const usbInput = document.getElementById('usb-scanner-input');
            if (usbInput) {
                usbInput.addEventListener('input', function(e) {
                    const value = e.target.value.trim();
                    if (value.length > 11) {  
                        e.target.value = '';  
                    }
                });
                
                // Keep USB input focused
                usbInput.addEventListener('blur', function() {
                    setTimeout(() => this.focus(), 100);
                });
                
                usbInput.focus();
            }

             document.getElementById('usb-toggle').addEventListener('click', function() {
                activateUsbScanner();
            });

            document.getElementById('webcam-toggle').addEventListener('click', function() {
                activateWebcamScanner();
            });
        }

        function activateUsbScanner() {
             document.getElementById('webcam-scanner-section').style.display = 'none';
            document.getElementById('usb-scanner-section').style.display = 'block';
            
             document.getElementById('usb-toggle').classList.add('active');
            document.getElementById('webcam-toggle').classList.remove('active');
            
             if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(err => console.log('Error stopping webcam:', err));
                html5QrcodeScanner = null;
            }
            
             setTimeout(() => {
                document.getElementById('usb-scanner-input').focus();
            }, 100);
        }

        function activateWebcamScanner() {
             document.getElementById('usb-scanner-section').style.display = 'none';
            document.getElementById('webcam-scanner-section').style.display = 'block';
            
             document.getElementById('webcam-toggle').classList.add('active');
            document.getElementById('usb-toggle').classList.remove('active');
            
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
             if (!decodedText || decodedText.trim().length === 0) {
                updateStatusCard('ERROR', 'INVALID QR', '--:--', 'Invalid QR Code');
                playNotificationSound(false);
                return;
            }

             const cleanedQRData = decodedText.trim();
            console.log('QR Data received:', cleanedQRData);

             if (!cleanedQRData.includes('_') || cleanedQRData.length < 5) {
                updateStatusCard('ERROR', 'INVALID FORMAT', '--:--', 'Expected: StudentID_Code');
                playNotificationSound(false);
                return;
            }

             const studentData = parseQRData(cleanedQRData);
            updateStudentDisplay(studentData);
            
             document.getElementById('loading-overlay').style.display = 'flex';

            fetch(`/attendance/${window.sessionData.token}/qr-verify`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": window.sessionData.csrfToken
                    },
                    body: JSON.stringify({
                        qr_data: cleanedQRData,
                        scanner_type: document.getElementById('usb-toggle').classList.contains('active') ? '2D Barcode Scanner' : 'Webcam'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                         updateStudentDisplayWithServerData(data.student, data);
                        currentStudentId = data.student.id;
                        updateAttendanceRecord(data.student.id);
                        
                         const now = new Date();
                        const timeStr = now.toLocaleTimeString('en-US', { 
                            hour: '2-digit', 
                            minute: '2-digit',
                            hour12: true 
                        });
                        
                        updateStatusCard('ATTENDANCE RECORDED!', data.time_period || 'TIME IN', timeStr, '{{ $now->format('F j, Y') }}');
                        playNotificationSound(true);

                        // Add student to recent bar
                        const timeOnly = now.toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: false
                        });
                        addStudentToRecentBar(data.student, timeOnly);
                        
                         setTimeout(() => {
                            resetStudentDisplay();
                            location.reload();  
                        }, 5000);
                    } else {
                        updateStatusCard('ACCESS DENIED', 'ERROR', '--:--', data.message || 'Please try again');
                        playNotificationSound(false);
                        
                         setTimeout(() => {
                            resetStudentDisplay();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    updateStatusCard('SYSTEM ERROR', 'ERROR', '--:--', 'Please try again');
                    playNotificationSound(false);
                    
                     setTimeout(() => {
                        resetStudentDisplay();
                    }, 3000);
                })
                .finally(() => {
                     document.getElementById('loading-overlay').style.display = 'none';
                });
        }

        function parseQRData(qrData) {
             if (qrData.includes('_')) {
                const parts = qrData.split('_');
                return {
                    id: parts[0],
                    name: 'Student ' + parts[0],
                    section: 'Loading...'
                };
            }
            
             return {
                id: qrData,
                name: 'Student ' + qrData,
                section: 'Loading...'
            };
        }

        function updateStudentDisplay(studentData) {
             const photoElement = document.getElementById('student-photo');
            photoElement.innerHTML = `<i class="fas fa-user-graduate"></i><div class="photo-text">PHOTO</div>`;

             document.getElementById('student-name').textContent = studentData.name;
            document.getElementById('student-section').textContent = studentData.section;

             updateStatusCard('PROCESSING...', 'SCANNING', '--:--', '{{ $now->format('F j, Y') }}');
        }

        function updateStudentDisplayWithServerData(studentData, result) {
             const photoElement = document.getElementById('student-photo');
            if (studentData.picture) {
                photoElement.innerHTML = `<img src="/storage/student_pictures/${studentData.picture}" alt="${studentData.name}">`;
            } else {
                photoElement.innerHTML = `<i class="fas fa-user-graduate"></i><div class="photo-text">PHOTO</div>`;
            }

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

        function addStudentToRecentBar(studentData, timeStr) {
            const container = document.getElementById('recent-students-container');
            if (!container) return;

            // Remove all empty tiles first
            const emptyTiles = container.querySelectorAll('.student-tile.empty');
            emptyTiles.forEach(tile => tile.remove());

            // Create new student tile
            const newTile = document.createElement('div');
            newTile.className = 'student-tile';
            newTile.innerHTML = `
                <div class="tile-time">TIME IN : ${timeStr}</div>
                <div class="tile-name">${studentData.name}</div>
                <div class="tile-section">${studentData.section || 'N/A'}</div>
            `;

            // Add animation
            newTile.style.opacity = '0';
            newTile.style.transform = 'scale(0.8)';
            
            container.insertBefore(newTile, container.firstChild);

            // Animate in
            setTimeout(() => {
                newTile.style.transition = 'all 0.3s ease';
                newTile.style.opacity = '1';
                newTile.style.transform = 'scale(1)';
            }, 10);

            // Keep only last 5 students
            const allTiles = container.querySelectorAll('.student-tile:not(.empty)');
            if (allTiles.length > 5) {
                for (let i = 5; i < allTiles.length; i++) {
                    allTiles[i].remove();
                }
            }

            // Add empty tiles if needed
            const remainingSlots = 5 - container.querySelectorAll('.student-tile:not(.empty)').length;
            for (let i = 0; i < remainingSlots; i++) {
                const emptyTile = document.createElement('div');
                emptyTile.className = 'student-tile empty';
                emptyTile.innerHTML = `
                    <div class="tile-time">TIME IN : --:--</div>
                    <div class="tile-name">STUDENT NAME</div>
                    <div class="tile-section">SECTION</div>
                `;
                container.appendChild(emptyTile);
            }
        }

        function resetStudentDisplay() {
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

            }
        }
    </script>



    
</body>
</html>