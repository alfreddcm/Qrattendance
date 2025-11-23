@php
// Build time range display for all teacher sections
$timeRangeDisplay = '';
if (isset($teacherSections) && $teacherSections->count() > 0) {
    $sectionDisplays = [];
    
    foreach ($teacherSections as $teacherSection) {
        $ranges = [];
        
        // Collect all time ranges for this section
        if ($teacherSection->am_time_in_start && $teacherSection->am_time_in_end) {
            $start = \Carbon\Carbon::parse($teacherSection->am_time_in_start);
            $end = \Carbon\Carbon::parse($teacherSection->am_time_in_end);
            $ranges[] = 'AM: ' . $start->format('g:i A') . '-' . $end->format('g:i A');
        }
        
        if ($teacherSection->pm_time_in_start && $teacherSection->pm_time_in_end) {
            $start = \Carbon\Carbon::parse($teacherSection->pm_time_in_start);
            $end = \Carbon\Carbon::parse($teacherSection->pm_time_in_end);
            $ranges[] = 'PM: ' . $start->format('g:i A') . '-' . $end->format('g:i A');
        }
        
        if (count($ranges) > 0) {
            $sectionDisplays[] = $teacherSection->name . ' – ' . implode(' | ', $ranges);
        }
    }
    
    $timeRangeDisplay = implode(' • ', $sectionDisplays);
} elseif ($section) {
    // Fallback to single section if no teacher sections found
    $ranges = [];
    
    if ($section->am_time_in_start && $section->am_time_in_end) {
        $start = \Carbon\Carbon::parse($section->am_time_in_start);
        $end = \Carbon\Carbon::parse($section->am_time_in_end);
        $ranges[] = 'AM: ' . $start->format('g:i A') . '-' . $end->format('g:i A');
    }
    
    if ($section->pm_time_in_start && $section->pm_time_in_end) {
        $start = \Carbon\Carbon::parse($section->pm_time_in_start);
        $end = \Carbon\Carbon::parse($section->pm_time_in_end);
        $ranges[] = 'PM: ' . $start->format('g:i A') . '-' . $end->format('g:i A');
    }
    
    if (count($ranges) > 0) {
        $timeRangeDisplay = $section->name . ' – ' . implode(' | ', $ranges);
    }
}
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $school->name ?? 'School' }} - Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100vh;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);
            padding: 6px;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            border-top: 3px solid #007bff;
            padding: 8px 16px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .school-logo {
            width: 50px;
            height: 50px;
            background: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
            text-transform: uppercase;
            overflow: hidden;
        }
        
        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .school-info h1 {
            font-size: 16px;
            color: #000;
            margin: 0;
            font-weight: 600;
        }

        .school-info p {
            font-size: 12px;
            color: #555;
            margin: 0;
        }

        .header-title {
            text-align: right;
            flex: 1;
        }

        .header-title h2 {
            font-size: 18px;
            color: #007bff;
            margin: 0;
            font-weight: 600;
        }

        .header-right {
            display: none;
        }

        .main-container {
            display: grid;
            grid-template-rows: 1fr auto;
            gap: 8px;
            flex: 1;
            min-height: 0;
            border-radius: 10px;
            background: white;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .top-section {
            display: grid;
            grid-template-columns: 350px 1fr 1fr;
            gap: 8px;
            min-height: 340px;
            max-height: 360px;
        }

        .student-photo-column {
            background: linear-gradient(145deg, #f8f9ff 0%, #e3f2fd 100%);
            border: 3px solid #007bff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            padding: 0;
            overflow: hidden;
            width: 100%;
            height: 100%;
        }
        
        .student-photo-placeholder {
            font-size: 60px;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .student-photo-column img {
            width: 100%;
            height: 100%;
            border-radius: 0;
            object-fit: cover;
        }

        .student-info-column {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 8px;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid #007bff;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 123, 255, 0.2);
        }

        .student-info-column > div {
            display: flex;
            flex-direction: column;
        }

        .text-label {
            font-size: 13px;
            font-weight: 600;
            color: #666;
            margin-top: 2px;
            margin-bottom: 0;
        }

        .line-placeholder {
            height: 1px;
            background: linear-gradient(to right, #007bff, #0056b3);
            width: 100%;
            margin-top: 4px;
            margin-bottom: 0;
        }

        .attendance-record-card {
            border: 2px solid #007bff;
            border-radius: 10px;
            overflow: hidden;
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
            box-shadow: 0 3px 10px rgba(0, 123, 255, 0.15);
        }

        .attendance-record-card .card-header {
            background: #007bff;
            color: white;
            padding: 8px 12px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: left;
        }

        .attendance-record-card .card-body {
            background: white;
            padding: 10px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .attendance-record-card .attendance-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0;
            font-size: 14px;
            background: transparent;
            border-bottom: none;
        }

        .attendance-record-card .attendance-line:last-child {
            border-bottom: none;
        }
        
        .attendance-record-card .attendance-line span:first-child {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .attendance-record-card .attendance-line span:last-child {
            font-weight: 700;
            color: #007bff;
            font-size: 16px;
            text-align: right;
        }

        .todays-attendance-card {
            border: 2px solid #FFC107;
            border-radius: 10px;
            overflow: hidden;
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
            box-shadow: 0 3px 10px rgba(255, 193, 7, 0.15);
        }

        .todays-attendance-card .card-header {
            background: #FFC107;
            color: white;
            padding: 8px 12px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: left;
        }

        .todays-attendance-card .card-body {
            background: white;
            padding: 10px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .todays-attendance-card .attendance-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0;
            font-size: 14px;
            background: transparent;
            border-bottom: none;
        }

        .todays-attendance-card .attendance-line:last-child {
            border-bottom: none;
        }

        .todays-attendance-card .attendance-line span:first-child {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .todays-attendance-card .attendance-line span:last-child {
            font-weight: 700;
            color: #FF9800;
            font-size: 16px;
            text-align: right;
        }

        .right-info-column {
            display: flex;
            flex-direction: column;
            padding: 8px;
            gap: 4px;
            background: linear-gradient(145deg, #ffffff 0%, #fff8f0 100%);
            border: 2px solid #FFC107;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(255, 193, 7, 0.2);
            text-align: left;
        }

        .date-label {
            font-size: 16px;
            font-weight: 700;
            color: #FF9800;
            line-height: 1.2;
        }

        .time-label {
            font-size: 38px;
            font-weight: 700;
            color: #007bff;
            line-height: 1.2;
        }

        .status-label {
            font-size: 17px;
            font-weight: 700;
            color: #FF9800;
            line-height: 1.2;
            padding: 8px 12px;
            background: rgba(255, 152, 0, 0.1);
            border-radius: 6px;
        }

        .qr-input-field {
            font-size: 17px;
            font-weight: 700;
            color: #FF9800;
            line-height: 1.2;
            padding: 8px 12px;
            background: rgba(255, 152, 0, 0.1);
            border: 2px solid #FFC107;
            border-radius: 6px;
            text-align: center;
            width: 100%;
            outline: none;
            transition: all 0.3s ease;
        }

        .qr-input-field::placeholder {
            color: #FF9800;
            opacity: 0.7;
        }

        .qr-input-field:focus {
            background: rgba(255, 152, 0, 0.15);
            border-color: #FF9800;
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
        }

        .recent-scans-gallery {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .scan-card {
            border: 2px solid #007bff;
            border-radius: 8px;
            overflow: hidden;
            display: grid;
            grid-template-rows: auto 1fr auto;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.25);
            position: relative;
            background: #ffffff;
        }

        .scan-card-header {
            background: #007bff;
            color: white;
            padding: 6px 4px;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .scan-card-body {
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            width: 100%;
        }

        .scan-card-body img {
            width: 100%;
            height: 100%;
            border-radius: 0;
            object-fit: cover;
        }

        .scan-photo-placeholder {
            font-size: 25px;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .scan-card-footer {
            background: #007bff;
            padding: 5px 3px;
            text-align: center;
            min-height: 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .scan-card-name {
            color: white;
            font-size: 10px;
            font-weight: 600;
            margin: 0;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .scan-card-section {
            color: white;
            font-size: 9px;
            font-weight: 600;
            margin: 1px 0 0 0;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .placeholder-text {
            color: #6c757d;
            font-style: italic;
        }

        @media (max-width: 1400px) {
            .top-section {
                grid-template-columns: 340px 1fr 1fr;
            }
        }
        @media (max-width: 1200px) {
            .top-section {
                grid-template-columns: 320px 1fr 1fr;
            }
        }
        @media (max-width: 1000px) {
            .top-section {
                grid-template-columns: 300px 1fr 1fr;
                min-height: 360px;
                max-height: 400px;
            }
            .scan-card-footer {
                min-height: 28px;
                padding: 3px 2px;
            }
        }
    </style>
</head>
<body>
     <div class="header">
        <div class="header-left">
            <div class="school-logo">
                @if($school->logo)
                    <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name ?? 'School' }} Logo">
                @else
                    {{ substr($school->name ?? 'S', 0, 1) }}
                @endif
            </div>
            <div class="school-info">
                <h1>{{ $school->name ?? 'San Guillermo Vocational and Industrial High School' }}</h1>
                <p>{{ $school->address ?? 'San Guillermo, Isabela' }}</p>
            </div>
        </div>
        
        <div class="header-title">
            <h2>Scan-to-Notify: A QR-Based Student Attendance and Parent Notification System</h2>
        </div>
        
        <div class="header-right">
            <div class="date-time">
                <div class="date-display" id="currentDate">{{ now()->format('D M d, Y') }}</div>
                <div class="time-display" id="currentTime">{{ now()->format('g:i:s A') }}</div>
            </div>
            <div class="status-message">
                @if(isset($student))
                    <span style="color: #28a745;">Student Detected</span>
                @else
                    &lt;WAITING TO SCAN..&gt;
                @endif
            </div>
        </div>
    </div>

     <div class="main-container">
         <div class="top-section">
             <div class="student-photo-column">
                @if($currentStudent && $currentStudent->picture)
                    <img src="{{ asset('storage/student_pictures/' . $currentStudent->picture) }}" alt="Student Photo">
                @else
                    <div class="student-photo-placeholder">
                        <i class="fa-solid fa-user"></i>
                    </div>
                @endif
             </div>

             <div class="student-info-column">
                 <div>
                     <div style="font-size: 24px; font-weight: 700; color: #007bff; margin-bottom: 4px; text-align: left;">
                         @if($currentStudent)
                             {{ $currentStudent->name }}
                         @else
                             <span style="color: #ccc; font-style: italic;">---</span>
                         @endif
                     </div>
                     <div class="text-label">Student Name</div>
                     <div class="line-placeholder"></div>
                 </div>

                 <div>
                     <div style="font-size: 20px; font-weight: 700; color: #FF9800; margin-bottom: 4px; text-align: left;">
                         @if($currentStudent && $currentStudent->section)
                             {{ $currentStudent->section->name }}
                         @else
                             <span style="color: #ccc; font-style: italic;">---</span>
                         @endif
                     </div>
                     <div class="text-label">Grade and Section</div>
                     <div class="line-placeholder"></div>
                 </div>

                <div class="attendance-record-card">
                    <div class="card-header">Attendance Record</div>
                    <div class="card-body">
                        <div class="attendance-line">
                            <span>Morning time in : @if($section && $section->am_time_in_start && $section->am_time_in_end){{ \Carbon\Carbon::parse($section->am_time_in_start)->format('g:i') }} – {{ \Carbon\Carbon::parse($section->am_time_in_end)->format('g:i') }} (time ranges)@endif</span>
                            <span>
                                @if($currentAttendanceRecord && $currentAttendanceRecord->time_in_am)
                                    {{ \Carbon\Carbon::parse($currentAttendanceRecord->time_in_am)->format('g:i A') }}
                                @else
                                    ---
                                @endif
                            </span>
                        </div>
                        <div class="attendance-line">
                            <span>Morning time out : @if($section && $section->am_time_out_start && $section->am_time_out_end){{ \Carbon\Carbon::parse($section->am_time_out_start)->format('g:i') }} – {{ \Carbon\Carbon::parse($section->am_time_out_end)->format('g:i') }} (time ranges)@endif</span>
                            <span>
                                @if($currentAttendanceRecord && $currentAttendanceRecord->time_out_am)
                                    {{ \Carbon\Carbon::parse($currentAttendanceRecord->time_out_am)->format('g:i A') }}
                                @else
                                    ---
                                @endif
                            </span>
                        </div>
                        <div class="attendance-line">
                            <span>Afternoon In: @if($section && $section->pm_time_in_start && $section->pm_time_in_end){{ \Carbon\Carbon::parse($section->pm_time_in_start)->format('g:i') }} – {{ \Carbon\Carbon::parse($section->pm_time_in_end)->format('g:i') }} (time ranges)@endif</span>
                            <span>
                                @if($currentAttendanceRecord && $currentAttendanceRecord->time_in_pm)
                                    {{ \Carbon\Carbon::parse($currentAttendanceRecord->time_in_pm)->format('g:i A') }}
                                @else
                                    ---
                                @endif
                            </span>
                        </div>
                        <div class="attendance-line">
                            <span>Afternoon Out: @if($section && $section->pm_time_out_start && $section->pm_time_out_end){{ \Carbon\Carbon::parse($section->pm_time_out_start)->format('g:i') }} – {{ \Carbon\Carbon::parse($section->pm_time_out_end)->format('g:i') }} (time ranges)@endif</span>
                            <span>
                                @if($currentAttendanceRecord && $currentAttendanceRecord->time_out_pm)
                                    {{ \Carbon\Carbon::parse($currentAttendanceRecord->time_out_pm)->format('g:i A') }}
                                @else
                                    ---
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

             <div class="right-info-column">
                 <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0; width: 100%;">
                     <div>
                         <div class="date-label" id="currentDate">TODAY IS: {{ now()->format('D M d, Y') }}</div>
                         <div class="time-label" id="currentTime">{{ now()->format('g:i:s A') }}</div>
                     </div>
                     <div class="date-label" style="font-size: 11px; color: #007bff; display: flex; align-items: center; justify-content: center; text-align: center; padding: 5px; line-height: 1.3;">
                         @if($timeRangeDisplay)
                             {{ $timeRangeDisplay }}
                         @else
                             <span style="color: #999;">Time ranges not configured</span>
                         @endif
                     </div>
                 </div>
                
                 @if($currentStudent)
                     <div class="status-label" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                         STUDENT DETECTED
                     </div>
                 @else
                     <input type="text" 
                            id="qrInput" 
                            class="qr-input-field" 
                            placeholder="WAITING TO SCAN..."
                            autocomplete="off"
                            autofocus>
                 @endif

                <div class="todays-attendance-card">
                    <div class="card-header">Todays Attendance</div>
                    <div class="card-body">
                        <div class="attendance-line">
                            <span>Morning In:</span>
                            <span>{{ $todaySummary['morning_in'] ?? 0 }}</span>
                        </div>
                        <div class="attendance-line">
                            <span>Morning Out:</span>
                            <span>{{ $todaySummary['morning_out'] ?? 0 }}</span>
                        </div>
                        <div class="attendance-line">
                            <span>Afternoon In:</span>
                            <span>{{ $todaySummary['afternoon_in'] ?? 0 }}</span>
                        </div>
                        <div class="attendance-line">
                            <span>Afternoon Out:</span>
                            <span>{{ $todaySummary['afternoon_out'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="recent-scans-gallery">
            @if(isset($recentAttendance) && $recentAttendance->count() > 0)
                @foreach($recentAttendance as $log)
                    @php
                        // Determine the most recent time and its type
                        $timeType = 'TIME IN';
                        $lastTime = null;
                        
                        if ($log->time_out_pm) {
                            $lastTime = \Carbon\Carbon::parse($log->time_out_pm)->format('g:i A');
                            $timeType = 'TIME OUT';
                        } elseif ($log->time_in_pm) {
                            $lastTime = \Carbon\Carbon::parse($log->time_in_pm)->format('g:i A');
                            $timeType = 'TIME IN';
                        } elseif ($log->time_out_am) {
                            $lastTime = \Carbon\Carbon::parse($log->time_out_am)->format('g:i A');
                            $timeType = 'TIME OUT';
                        } elseif ($log->time_in_am) {
                            $lastTime = \Carbon\Carbon::parse($log->time_in_am)->format('g:i A');
                            $timeType = 'TIME IN';
                        }
                        
                        $timeInfo = $lastTime ? "{$timeType}: {$lastTime}" : "TIME IN: ---";
                    @endphp
                    <div class="scan-card">
                        <div class="scan-card-header">{{ $timeInfo }}</div>
                        <div class="scan-card-body">
                            @if($log->student && $log->student->picture)
                                <img src="{{ asset('storage/student_pictures/' . $log->student->picture) }}" alt="Student">
                            @else
                                <div class="scan-photo-placeholder">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            @endif
                        </div>
                        <div class="scan-card-footer">
                            <div class="scan-card-name">{{ $log->student->name ?? '---' }}</div>
                            <div class="scan-card-section">{{ $log->student->section->name ?? '---' }}</div>
                        </div>
                    </div>
                @endforeach

                @for($i = $recentAttendance->count(); $i < 7; $i++)
                    <div class="scan-card">
                        <div class="scan-card-header">TIME IN: ---</div>
                        <div class="scan-card-body">
                            <div class="scan-photo-placeholder">
                                <i class="fa-solid fa-user"></i>
                            </div>
                        </div>
                        <div class="scan-card-footer">
                            <div class="scan-card-name">---</div>
                            <div class="scan-card-section">---</div>
                        </div>
                    </div>
                @endfor
            @else
                @for($i = 0; $i < 7; $i++)
                    <div class="scan-card">
                        <div class="scan-card-header">TIME IN: ---</div>
                        <div class="scan-card-body">
                            <div class="scan-photo-placeholder">
                                <i class="fa-solid fa-user"></i>
                            </div>
                        </div>
                        <div class="scan-card-footer">
                            <div class="scan-card-name">---</div>
                            <div class="scan-card-section">---</div>
                        </div>
                    </div>
                @endfor
            @endif
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            
            const options = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            };
            const dateStr = now.toLocaleDateString('en-US', options).toUpperCase();
            
            const timeStr = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            const mainDateEl = document.querySelector('.right-info-column #currentDate');
            if (mainDateEl) mainDateEl.textContent = `TODAY IS: ${dateStr}`;
            
            const mainTimeEl = document.querySelector('.right-info-column #currentTime');
            if (mainTimeEl) mainTimeEl.textContent = timeStr;

            const headerDateEl = document.querySelector('.header #currentDate');
            if (headerDateEl) headerDateEl.textContent = dateStr;
            
            const headerTimeEl = document.querySelector('.header #currentTime');
            if (headerTimeEl) headerTimeEl.textContent = timeStr;
        }

        updateClock();
        setInterval(updateClock, 1000);

        // Auto-clear student data after 5 seconds (if student present on page load)
        @if($currentStudent)
        console.log('Student detected on page load, will clear in 5 seconds');
        setTimeout(function() {
            console.log('5 seconds elapsed, clearing student display...');
            clearStudentDisplay();
        }, 5000);
        @endif

        // QR Code Scanner and Data Management
        let clearStudentTimeout = null;
        let qrTimeout = null; // Declare qrTimeout variable

        // Function to fetch and display student data
        function fetchStudentData() {
            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.currentStudent) {
                    displayStudent(data.currentStudent, data.currentAttendanceRecord);
                    updateRecentAttendance(data.recentAttendance);
                    
                    // Clear after 5 seconds
                    if (clearStudentTimeout) {
                        clearTimeout(clearStudentTimeout);
                    }
                    clearStudentTimeout = setTimeout(() => {
                        clearStudentDisplay();
                    }, 5000);
                } else {
                    // If no student data, just update recent attendance
                    updateRecentAttendance(data.recentAttendance);
                }
            })
            .catch(error => {
                console.error('Error fetching student data:', error);
            });
        }

        // Function to display student
        function displayStudent(student, attendance) {
            console.log('Displaying student:', student);
            
            // Update photo
            const photoColumn = document.querySelector('.student-photo-column');
            if (photoColumn) {
                if (student.picture) {
                    photoColumn.innerHTML = `<img src="{{ asset('storage/student_pictures/') }}/${student.picture}" alt="Student Photo">`;
                } else {
                    photoColumn.innerHTML = `<div class="student-photo-placeholder"><i class="fa-solid fa-user"></i></div>`;
                }
            }

            // Update student name
            const nameEl = document.querySelector('.student-info-column > div:first-child > div:first-child');
            if (nameEl) {
                nameEl.innerHTML = `${student.name}`;
                nameEl.style.color = '#007bff';
            }

            // Update section
            const sectionEl = document.querySelector('.student-info-column > div:nth-child(2) > div:first-child');
            if (sectionEl) {
                sectionEl.innerHTML = student.section ? student.section.name : '<span style="color: #ccc; font-style: italic;">---</span>';
                sectionEl.style.color = '#FF9800';
            }

            // Update attendance record in the card
            if (attendance) {
                const attendanceLines = document.querySelectorAll('.attendance-record-card .attendance-line span:last-child');
                if (attendanceLines[0]) attendanceLines[0].textContent = attendance.time_in_am ? formatTime(attendance.time_in_am) : '---';
                if (attendanceLines[1]) attendanceLines[1].textContent = attendance.time_out_am ? formatTime(attendance.time_out_am) : '---';
                if (attendanceLines[2]) attendanceLines[2].textContent = attendance.time_in_pm ? formatTime(attendance.time_in_pm) : '---';
                if (attendanceLines[3]) attendanceLines[3].textContent = attendance.time_out_pm ? formatTime(attendance.time_out_pm) : '---';
            }

            // Replace QR input with status label
            const qrInputField = document.getElementById('qrInput');
            if (qrInputField) {
                qrInputField.outerHTML = '<div class="status-label" id="statusLabel" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">STUDENT DETECTED</div>';
            }
        }

        // Function to clear student display
        function clearStudentDisplay() {
            // Call server to clear session
            fetch(`/public/attendance/{{ $attendanceCode->code }}/clear`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(() => {
                console.log('Session cleared, resetting display');
                
                // Reset photo
                const photoColumn = document.querySelector('.student-photo-column');
                if (photoColumn) {
                    photoColumn.innerHTML = `<div class="student-photo-placeholder"><i class="fa-solid fa-user"></i></div>`;
                }

                // Reset name
                const nameEl = document.querySelector('.student-info-column > div:first-child > div:first-child');
                if (nameEl) {
                    nameEl.innerHTML = '<span style="color: #ccc; font-style: italic;">---</span>';
                }

                // Reset section
                const sectionEl = document.querySelector('.student-info-column > div:nth-child(2) > div:first-child');
                if (sectionEl) {
                    sectionEl.innerHTML = '<span style="color: #ccc; font-style: italic;">---</span>';
                }

                // Reset attendance records in the card
                const attendanceLines = document.querySelectorAll('.attendance-record-card .attendance-line span:last-child');
                attendanceLines.forEach(line => {
                    line.textContent = '---';
                });

                // Replace status label with QR input
                const statusLabel = document.getElementById('statusLabel');
                if (statusLabel) {
                    statusLabel.outerHTML = '<input type="text" id="qrInput" class="qr-input-field" placeholder="WAITING TO SCAN..." autocomplete="off" autofocus>';
                    
                    // Re-attach event listeners to new input
                    attachQRInputListeners();
                }
                
                // Reload recent attendance list
                fetchRecentAttendance();
            });
        }
        
        // Function to fetch and update recent attendance without full reload
        function fetchRecentAttendance() {
            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.recentAttendance) {
                    updateRecentAttendance(data.recentAttendance);
                }
            })
            .catch(error => {
                console.error('Error fetching recent attendance:', error);
            });
        }

        // Function to update recent attendance
        function updateRecentAttendance(recentData) {
            console.log('Updating recent attendance with data:', recentData);
            
            if (!recentData || recentData.length === 0) {
                console.log('No recent data to update');
                return;
            }
            
            // Build HTML for each recent attendance record
            const recentHTML = recentData.map(record => {
                // Determine time info
                let timeInfo = 'TIME IN: ---';
                let lastTime = null;
                
                if (record.time_out_pm) {
                    lastTime = formatTime(record.time_out_pm);
                    timeInfo = `TIME OUT: ${lastTime}`;
                } else if (record.time_in_pm) {
                    lastTime = formatTime(record.time_in_pm);
                    timeInfo = `TIME IN: ${lastTime}`;
                } else if (record.time_out_am) {
                    lastTime = formatTime(record.time_out_am);
                    timeInfo = `TIME OUT: ${lastTime}`;
                } else if (record.time_in_am) {
                    lastTime = formatTime(record.time_in_am);
                    timeInfo = `TIME IN: ${lastTime}`;
                }
                
                return `
                    <div class="scan-card">
                        <div class="scan-card-header">${timeInfo}</div>
                        <div class="scan-card-body">
                            ${record.student && record.student.picture ? 
                                `<img src="{{ asset('storage/student_pictures/') }}/${record.student.picture}" alt="Student">` :
                                '<div class="scan-photo-placeholder"><i class="fa-solid fa-user"></i></div>'
                            }
                        </div>
                        <div class="scan-card-footer">
                            <div class="scan-card-name">${record.student ? record.student.name : '---'}</div>
                            <div class="scan-card-section">${record.student && record.student.section ? record.student.section.name : '---'}</div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Fill remaining slots up to 7
            let fillHTML = '';
            for (let i = recentData.length; i < 7; i++) {
                fillHTML += `
                    <div class="scan-card">
                        <div class="scan-card-header">TIME IN: ---</div>
                        <div class="scan-card-body">
                            <div class="scan-photo-placeholder"><i class="fa-solid fa-user"></i></div>
                        </div>
                        <div class="scan-card-footer">
                            <div class="scan-card-name">---</div>
                            <div class="scan-card-section">---</div>
                        </div>
                    </div>
                `;
            }
            
            // Find the recent scans gallery and update it
            const recentScansGallery = document.querySelector('.recent-scans-gallery');
            if (recentScansGallery) {
                recentScansGallery.innerHTML = recentHTML + fillHTML;
                console.log('Recent attendance updated successfully');
            } else {
                console.error('Recent scans gallery not found');
            }
        }

        // Helper function to format time
        function formatTime(timeString) {
            if (!timeString) return '---';
            
            try {
                const date = new Date(timeString);
                
                // Check if date is valid
                if (isNaN(date.getTime())) {
                    console.error('Invalid date:', timeString);
                    return '---';
                }
                
                return date.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit',
                    hour12: true 
                });
            } catch (error) {
                console.error('Error formatting time:', timeString, error);
                return '---';
            }
        }

        // Function to attach QR input listeners
        function attachQRInputListeners() {
            const qrInput = document.getElementById('qrInput');
            if (!qrInput) {
                console.log('QR input field not found');
                return;
            }
            
            console.log('Attaching QR input listeners to field');
            qrInput.focus();

            // Handle QR code input - barcode scanners type fast and press Enter
            qrInput.addEventListener('input', function(e) {
                const value = e.target.value.trim();
                console.log('Input event - value:', value, 'length:', value.length);
                
                // Clear existing timeout
                if (qrTimeout) {
                    clearTimeout(qrTimeout);
                }
                
                // Don't auto-submit on input, wait for Enter key
                // Barcode scanners will send Enter when done
            });

            // Handle Enter key (most barcode scanners send Enter)
            qrInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const value = e.target.value.trim();
                    console.log('Enter pressed - value:', value);
                    
                    if (qrTimeout) {
                        clearTimeout(qrTimeout);
                    }
                    
                    if (value) {
                        console.log('Submitting on Enter:', value);
                        processQRCode(value);
                        qrInput.value = ''; // Clear immediately
                    }
                }
            });
            
            console.log('QR input listeners attached successfully');
        }
        
        // Keep focus on QR input (separate from attachQRInputListeners to avoid duplicates)
        function maintainQRFocus() {
            const input = document.getElementById('qrInput');
            if (input && document.activeElement !== input) {
                input.focus();
            }
        }
        
        // Maintain focus every 500ms
        setInterval(maintainQRFocus, 500);
        
        // Initial attachment of listeners
        attachQRInputListeners();

        // Process QR Code function
        function processQRCode(studentId) {
            console.log('=== Processing QR code:', studentId, '===');
            
            const qrInput = document.getElementById('qrInput');
            if (!qrInput) {
                console.error('QR input not found in processQRCode');
                return;
            }
            
            // Clear the input immediately
            qrInput.value = '';
            qrInput.placeholder = 'PROCESSING...';
            qrInput.disabled = true;
            
            console.log('Sending attendance record request...');

            // Make API call to record attendance
            fetch('{{ route("public.attendance.scan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    student_id: studentId,
                    qr_data: studentId
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Scan API response:', data);
                if (data.success) {
                    console.log('✓ Attendance recorded successfully, fetching student data...');
                    // Fetch and display student data instead of reloading
                    fetchStudentData();
                } else {
                    // Show error in placeholder instead of alert
                    console.error('✗ Scan failed:', data.message);
                    qrInput.value = '';
                    
                    // Simplify error messages for placeholder
                    let errorMsg = data.message;
                    if (errorMsg.includes('not found')) {
                        errorMsg = 'Student not found!';
                    } else if (errorMsg.includes('already') || errorMsg.includes('duplicate')) {
                        errorMsg = 'Already recorded!';
                    }
                    
                    qrInput.placeholder = errorMsg;
                    qrInput.disabled = false;
                    qrInput.focus();
                    
                    // Reset placeholder after 3 seconds
                    setTimeout(() => {
                        qrInput.placeholder = 'WAITING TO SCAN...';
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('✗ Error during scan:', error);
                qrInput.value = '';
                qrInput.placeholder = 'Error! Please try again.';
                qrInput.disabled = false;
                qrInput.focus();
                
                // Reset placeholder after 3 seconds
                setTimeout(() => {
                    qrInput.placeholder = 'WAITING TO SCAN...';
                }, 3000);
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>