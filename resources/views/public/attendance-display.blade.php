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
            background: #f0f2f5;
            padding: 8px;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: white;
            border: none;
            border-radius: 0;
            box-shadow: none;
            border-top: 4px solid #4169E1;
            padding: 10px 20px;
            margin-bottom: 8px;
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
            background: #4169E1;
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
            color: #4169E1;
            margin: 0;
            font-weight: 600;
        }

        .header-right {
            display: none;
        }

        .main-container {
            display: grid;
            grid-template-rows: 1fr auto;
            gap: 10px;
            flex: 1;
            min-height: 0;
            border: 2px solid #000;
            border-radius: 8px;
            background: #fff;
            padding: 8px;
        }

        .top-section {
            display: grid;
            grid-template-columns: 280px 1fr 1fr;
            gap: 8px;
            min-height: 400px;
            max-height: 450px;
        }

        .student-photo-column {
            background: white;
            border: 2px solid #000;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 0;
            overflow: hidden;
            min-height: 280px;
            max-height: 350px;
            width: 100%;
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
            gap: 12px;
            padding: 15px;
            background: white;
            border: 2px solid #000;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .student-info-column > div {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .text-label {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-top: 4px;
            margin-bottom: 2px;
        }

        .line-placeholder {
            height: 2px;
            background: #000;
            width: 100%;
            margin-bottom: 12px;
        }

        .attendance-record-card {
            border: 2px solid #000;
            border-radius: 6px;
            overflow: hidden;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-header {
            background: #4169E1;
            color: white;
            padding: 8px 12px;
            font-weight: 600;
            font-size: 13px;
        }

        .card-body {
            background: white;
            padding: 10px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .attendance-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 13px;
            color: #000;
        }
        
        .attendance-line span:first-child {
            font-weight: 600;
        }

        .attendance-record-card .attendance-line span:last-child {
            font-weight: 600;
            color: #000;
        }


        .attendance-line:last-child {
            margin-bottom: 0;
        }

        .right-info-column {
            display: flex;
            flex-direction: column;
            padding: 15px;
            gap: 8px;
            background: white;
            border: 2px solid #000;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .date-label {
            font-size: 14px;
            font-weight: 700;
            color: #000;
            line-height: 1.3;
        }

        .time-label {
            font-size: 28px;
            font-weight: 700;
            color: #000;
            line-height: 1.3;
        }

        .status-label {
            font-size: 14px;
            font-weight: 700;
            color: #000;
            line-height: 1.3;
        }

        .todays-attendance-card {
            border: 2px solid #000;
            border-radius: 6px;
            overflow: hidden;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-self: stretch;
        }
        
        .todays-attendance-card .attendance-line span:last-child {
            font-weight: 600;
            color: #000;
        }

        .recent-scans-gallery {
            height: 140px;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
        }

        .scan-card {
            border: 2px solid #4169E1;
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            height: 100%;
        }

        .scan-card::before {
            content: attr(data-time-info);
            background: #4169E1;
            color: white;
            padding: 3px 2px;
            font-size: 10px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .scan-card-body {
            background: white;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            min-height: 60px;
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
            background: #4169E1;
            padding: 4px 2px;
            text-align: center;
            min-height: 35px;
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
                grid-template-columns: 260px 1fr 1fr;
            }
        }
        @media (max-width: 1200px) {
            .top-section {
                grid-template-columns: 220px 1fr 1fr;
            }
        }
        @media (max-width: 1000px) {
            .top-section {
                grid-template-columns: 200px 1fr 1fr;
            }
            .recent-scans-gallery {
                height: 120px;
            }
            .scan-card-footer {
                min-height: 30px;
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
                    <img src="{{ asset('storage/' . $currentStudent->picture) }}" alt="Student Photo">
                @else
                    <div class="student-photo-placeholder">
                        <i class="fa-solid fa-user"></i>
                    </div>
                @endif
             </div>

             <div class="student-info-column">
                 <div>
                     <div style="font-size: 24px; font-weight: 700; color: #000; margin-bottom: 4px;">
                         {{ $currentStudent->name ?? '---' }}
                     </div>
                     <div class="text-label">Student Name</div>
                     <div class="line-placeholder"></div>
                 </div>

                 <div>
                     <div style="font-size: 22px; font-weight: 700; color: #000; margin-bottom: 4px;">
                         {{ $currentStudent->section->name ?? '---' }}
                     </div>
                     <div class="text-label">Grade and Section</div>
                     <div class="line-placeholder"></div>
                 </div>

                <div class="attendance-record-card">
                    <div class="card-header">Attendance Record</div>
                    <div class="card-body">
                        <div class="attendance-line">
                            <span>Morning In:</span>
                            <span>{{ $currentAttendanceRecord && $currentAttendanceRecord->time_in_am ? \Carbon\Carbon::parse($currentAttendanceRecord->time_in_am)->format('g:i A') : '---' }}</span>
                        </div>
                        <div class="attendance-line">
                            <span>Morning Out:</span>
                            <span>{{ $currentAttendanceRecord && $currentAttendanceRecord->time_out_am ? \Carbon\Carbon::parse($currentAttendanceRecord->time_out_am)->format('g:i A') : '---' }}</span>
                        </div>
                        <div class="attendance-line">
                            <span>Afternoon In:</span>
                            <span>{{ $currentAttendanceRecord && $currentAttendanceRecord->time_in_pm ? \Carbon\Carbon::parse($currentAttendanceRecord->time_in_pm)->format('g:i A') : '---' }}</span>
                        </div>
                        <div class="attendance-line">
                            <span>Afternoon Out:</span>
                            <span>{{ $currentAttendanceRecord && $currentAttendanceRecord->time_out_pm ? \Carbon\Carbon::parse($currentAttendanceRecord->time_out_pm)->format('g:i A') : '---' }}</span>
                        </div>
                    </div>
                </div>
            </div>

             <div class="right-info-column">
                 <div class="date-label" id="currentDate">TODAY IS: {{ now()->format('D M d, Y') }}</div>
                
                 <div class="time-label" id="currentTime">{{ now()->format('g:i:s A') }}</div>
                
                 <div class="status-label">
                     @if($currentStudent)
                         STUDENT DETECTED
                     @else
                         WAITING TO SCAN..
                     @endif
                 </div>

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
                    <div class="scan-card" data-time-info="{{ $timeInfo }}">
                        <div class="scan-card-body">
                            @if($log->student && $log->student->picture)
                                <img src="{{ asset('storage/' . $log->student->picture) }}" alt="Student">
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

                @for($i = $recentAttendance->count(); $i < 5; $i++)
                    <div class="scan-card" data-time-info="TIME IN: ---">
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
                @for($i = 0; $i < 5; $i++)
                    <div class="scan-card" data-time-info="TIME IN: ---">
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

        setInterval(function() {
            window.location.reload();
        }, 30000);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>