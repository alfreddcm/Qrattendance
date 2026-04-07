@php
// Build time range display for all teacher sections
$timeRangeDisplay = '';

if (isset($teacherSections) && $teacherSections->count() > 0) {
    $sectionDisplays = [];
    
    foreach ($teacherSections as $teacherSection) {
        $ranges = [];
        
        // Morning time window
        if ($teacherSection->am_time_in_start && $teacherSection->am_time_in_end && $teacherSection->am_time_out_start && $teacherSection->am_time_out_end) {
            $inStart = \Carbon\Carbon::parse($teacherSection->am_time_in_start);
            $inEnd = \Carbon\Carbon::parse($teacherSection->am_time_in_end);
            $outStart = \Carbon\Carbon::parse($teacherSection->am_time_out_start);
            $outEnd = \Carbon\Carbon::parse($teacherSection->am_time_out_end);
            $ranges[] = 'AM: IN ' . $inStart->format('g:i A') . '-' . $inEnd->format('g:i A') . ', OUT ' . $outStart->format('g:i A') . '-' . $outEnd->format('g:i A');
        } elseif ($teacherSection->am_time_in_start && $teacherSection->am_time_in_end) {
            $inStart = \Carbon\Carbon::parse($teacherSection->am_time_in_start);
            $inEnd = \Carbon\Carbon::parse($teacherSection->am_time_in_end);
            $ranges[] = 'AM: IN ' . $inStart->format('g:i A') . '-' . $inEnd->format('g:i A');
        }
        
        // Afternoon time window
        if ($teacherSection->pm_time_in_start && $teacherSection->pm_time_in_end && $teacherSection->pm_time_out_start && $teacherSection->pm_time_out_end) {
            $inStart = \Carbon\Carbon::parse($teacherSection->pm_time_in_start);
            $inEnd = \Carbon\Carbon::parse($teacherSection->pm_time_in_end);
            $outStart = \Carbon\Carbon::parse($teacherSection->pm_time_out_start);
            $outEnd = \Carbon\Carbon::parse($teacherSection->pm_time_out_end);
            $ranges[] = 'PM: IN ' . $inStart->format('g:i A') . '-' . $inEnd->format('g:i A') . ', OUT ' . $outStart->format('g:i A') . '-' . $outEnd->format('g:i A');
        } elseif ($teacherSection->pm_time_in_start && $teacherSection->pm_time_in_end) {
            $inStart = \Carbon\Carbon::parse($teacherSection->pm_time_in_start);
            $inEnd = \Carbon\Carbon::parse($teacherSection->pm_time_in_end);
            $ranges[] = 'PM: IN ' . $inStart->format('g:i A') . '-' . $inEnd->format('g:i A');
        }
        
        if (count($ranges) > 0) {
            $sectionDisplays[] = $teacherSection->name . ' – ' . implode(' | ', $ranges);
        }
    }
    
    $timeRangeDisplay = implode(' • ', $sectionDisplays);
} elseif ($section) {
    $ranges = [];
    
    // Morning time window
    if ($section->am_time_in_start && $section->am_time_in_end && $section->am_time_out_start && $section->am_time_out_end) {
        $inStart = \Carbon\Carbon::parse($section->am_time_in_start);
        $inEnd = \Carbon\Carbon::parse($section->am_time_in_end);
        $outStart = \Carbon\Carbon::parse($section->am_time_out_start);
        $outEnd = \Carbon\Carbon::parse($section->am_time_out_end);
        $ranges[] = 'AM: IN ' . $inStart->format('g:i A') . '-' . $inEnd->format('g:i A') . ', OUT ' . $outStart->format('g:i A') . '-' . $outEnd->format('g:i A');
    } elseif ($section->am_time_in_start && $section->am_time_in_end) {
        $inStart = \Carbon\Carbon::parse($section->am_time_in_start);
        $inEnd = \Carbon\Carbon::parse($section->am_time_in_end);
        $ranges[] = 'AM: IN ' . $inStart->format('g:i A') . '-' . $inEnd->format('g:i A');
    }
    
    // Afternoon time window
    if ($section->pm_time_in_start && $section->pm_time_in_end && $section->pm_time_out_start && $section->pm_time_out_end) {
        $inStart = \Carbon\Carbon::parse($section->pm_time_in_start);
        $inEnd = \Carbon\Carbon::parse($section->pm_time_in_end);
        $outStart = \Carbon\Carbon::parse($section->pm_time_out_start);
        $outEnd = \Carbon\Carbon::parse($section->pm_time_out_end);
        $ranges[] = 'PM: IN ' . $inStart->format('g:i A') . '-' . $inEnd->format('g:i A') . ', OUT ' . $outStart->format('g:i A') . '-' . $outEnd->format('g:i A');
    } elseif ($section->pm_time_in_start && $section->pm_time_in_end) {
        $inStart = \Carbon\Carbon::parse($section->pm_time_in_start);
        $inEnd = \Carbon\Carbon::parse($section->pm_time_in_end);
        $ranges[] = 'PM: IN ' . $inStart->format('g:i A') . '-' . $inEnd->format('g:i A');
    }
    
    if (count($ranges) > 0) {
        $timeRangeDisplay = $section->name . ' – ' . implode(' | ', $ranges);
    }
}

$schoolLogoUrl = null;
if (!empty($school?->logo)) {
    $normalizedLogoPath = ltrim(str_replace('storage/', '', $school->logo), '/');
    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedLogoPath)) {
        $schoolLogoUrl = asset('storage/' . $normalizedLogoPath);
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
                @if($schoolLogoUrl)
                    <img src="{{ $schoolLogoUrl }}" alt="{{ $school->name ?? 'School' }} Logo">
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
             <div class="student-photo-column" id="studentPreviewPhoto">
                @if($currentStudent && $currentStudent->picture)
                    <img src="{{ asset('storage/student_pictures/' . $currentStudent->picture) }}" alt="Student Photo">
                @else
                    <div class="student-photo-placeholder">
                        <i class="fa-solid fa-user"></i>
                    </div>
                @endif
             </div>

             <div class="student-info-column" id="studentPreviewContainer">
                 <div>
                     <div id="studentName" style="font-size: 24px; font-weight: 700; color: #007bff; margin-bottom: 4px; text-align: left;">
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
                     <div id="studentSection" style="font-size: 20px; font-weight: 700; color: #FF9800; margin-bottom: 4px; text-align: left;">
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
                            <span id="timeInAmValue">
                                @if($currentAttendanceRecord && $currentAttendanceRecord->time_in_am)
                                    {{ \Carbon\Carbon::parse($currentAttendanceRecord->time_in_am)->format('g:i A') }}
                                @else
                                    ---
                                @endif
                            </span>
                        </div>
                        <div class="attendance-line">
                            <span>Morning time out : @if($section && $section->am_time_out_start && $section->am_time_out_end){{ \Carbon\Carbon::parse($section->am_time_out_start)->format('g:i') }} – {{ \Carbon\Carbon::parse($section->am_time_out_end)->format('g:i') }} (time ranges)@endif</span>
                            <span id="timeOutAmValue">
                                @if($currentAttendanceRecord && $currentAttendanceRecord->time_out_am)
                                    {{ \Carbon\Carbon::parse($currentAttendanceRecord->time_out_am)->format('g:i A') }}
                                @else
                                    ---
                                @endif
                            </span>
                        </div>
                        <div class="attendance-line">
                            <span>Afternoon In: @if($section && $section->pm_time_in_start && $section->pm_time_in_end){{ \Carbon\Carbon::parse($section->pm_time_in_start)->format('g:i') }} – {{ \Carbon\Carbon::parse($section->pm_time_in_end)->format('g:i') }} (time ranges)@endif</span>
                            <span id="timeInPmValue">
                                @if($currentAttendanceRecord && $currentAttendanceRecord->time_in_pm)
                                    {{ \Carbon\Carbon::parse($currentAttendanceRecord->time_in_pm)->format('g:i A') }}
                                @else
                                    ---
                                @endif
                            </span>
                        </div>
                        <div class="attendance-line">
                            <span>Afternoon Out: @if($section && $section->pm_time_out_start && $section->pm_time_out_end){{ \Carbon\Carbon::parse($section->pm_time_out_start)->format('g:i') }} – {{ \Carbon\Carbon::parse($section->pm_time_out_end)->format('g:i') }} (time ranges)@endif</span>
                            <span id="timeOutPmValue">
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
                
                <div 
                    class="status-label" 
                    id="scanStatusLabel"
                    style="background: rgba(40, 167, 69, 0.1); color: #28a745; display: {{ $currentStudent ? 'block' : 'none' }};">
                    {{ $currentStudent ? 'STUDENT DETECTED' : 'WAITING TO SCAN...' }}
                </div>
                <input type="text" 
                        id="qrInput" 
                        class="qr-input-field" 
                        placeholder="WAITING TO SCAN..."
                        autocomplete="off"
                        autofocus
                        style="{{ $currentStudent ? 'display:none;' : 'display:block;' }}">

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

        <div class="recent-scans-gallery" id="attendanceHistoryContainer">
            @include('public.partials.attendance-history', ['recentAttendance' => $recentAttendance])
        </div>
    </div>

    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const scanUrl = '{{ route("public.attendance.scan") }}';
            const attendanceCode = '{{ $attendanceCode->code }}';
            const historyUrl = '{{ route("public.attendance.recent", ["code" => $attendanceCode->code]) }}';

            let previewTimer = null;
            let scanSeq = 0;
            let historyController = null;
            let scanController = null;

            function updateClock() {
                const now = new Date();
                const dateStr = now.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }).toUpperCase();
                const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });

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

            updateClock();
            setInterval(updateClock, 1000);

            function normalizeTime(value) {
                if (!value) return '---';
                 if (/am|pm/i.test(value)) return value;

                 const isoGuess = Date.parse(value);
                if (!Number.isNaN(isoGuess)) {
                    return new Date(isoGuess).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                }

                return '---';
            }

            function setAttendanceValue(elId, value) {
                const el = document.getElementById(elId);
                if (!el) return;
                el.textContent = normalizeTime(value) || '---';
            }

            function setWaitingState() {
                const photo = document.getElementById('studentPreviewPhoto');
                if (photo) {
                    photo.innerHTML = '<div class="student-photo-placeholder"><i class="fa-solid fa-user"></i></div>';
                }

                const nameEl = document.getElementById('studentName');
                if (nameEl) {
                    nameEl.innerHTML = '<span style="color: #ccc; font-style: italic;">---</span>';
                }

                const sectionEl = document.getElementById('studentSection');
                if (sectionEl) {
                    sectionEl.innerHTML = '<span style="color: #ccc; font-style: italic;">---</span>';
                }

                setAttendanceValue('timeInAmValue', null);
                setAttendanceValue('timeOutAmValue', null);
                setAttendanceValue('timeInPmValue', null);
                setAttendanceValue('timeOutPmValue', null);

                const statusLabel = document.getElementById('scanStatusLabel');
                if (statusLabel) {
                    statusLabel.style.display = 'block';
                    statusLabel.textContent = 'WAITING TO SCAN...';
                    statusLabel.style.color = '#FF9800';
                }

                const input = document.getElementById('qrInput');
                if (input) {
                    input.style.display = 'block';
                    input.style.opacity = '1';
                    input.disabled = false;
                    input.placeholder = 'WAITING TO SCAN...';
                    input.focus();
                }
            }

            function showStudentPreview(payload) {
                const seq = ++scanSeq;
                if (previewTimer) {
                    clearTimeout(previewTimer);
                }

                const student = payload.student || payload.currentStudent;
                const attendance = payload.attendance || payload.currentAttendanceRecord || payload.attendance_record;

                if (student) {
                    const photo = document.getElementById('studentPreviewPhoto');
                    if (photo) {
                        if (student.picture) {
                            photo.innerHTML = `<img src="${student.picture}" alt="Student Photo">`;
                        } else {
                            photo.innerHTML = '<div class="student-photo-placeholder"><i class="fa-solid fa-user"></i></div>';
                        }
                    }

                    const nameEl = document.getElementById('studentName');
                    if (nameEl) {
                        nameEl.textContent = student.name || '---';
                    }

                    const sectionEl = document.getElementById('studentSection');
                    if (sectionEl) {
                        sectionEl.textContent = (student.section && student.section.name) ? student.section.name : '---';
                    }
                }

                if (attendance) {
                    setAttendanceValue('timeInAmValue', attendance.time_in_am_formatted || attendance.time_in_am);
                    setAttendanceValue('timeOutAmValue', attendance.time_out_am_formatted || attendance.time_out_am);
                    setAttendanceValue('timeInPmValue', attendance.time_in_pm_formatted || attendance.time_in_pm);
                    setAttendanceValue('timeOutPmValue', attendance.time_out_pm_formatted || attendance.time_out_pm);
                }

                const statusLabel = document.getElementById('scanStatusLabel');
                if (statusLabel) {
                    statusLabel.style.display = 'block';
                    statusLabel.textContent = 'STUDENT DETECTED';
                    statusLabel.style.color = '#28a745';
                }

                const input = document.getElementById('qrInput');
                if (input) {
                    input.style.opacity = '0';
                    input.value = '';
                    input.focus();
                }

                previewTimer = setTimeout(() => {
                    if (scanSeq === seq) {
                        setWaitingState();
                    }
                }, 5000);
            }

            function reloadAttendanceHistory() {
                if (historyController) {
                    historyController.abort();
                }

                historyController = new AbortController();
                const container = document.getElementById('attendanceHistoryContainer');
                if (!container) return Promise.resolve();

                return fetch(`${historyUrl}?format=html&t=${Date.now()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                        'Cache-Control': 'no-cache, no-store, must-revalidate'
                    },
                    signal: historyController.signal,
                    cache: 'no-store'
                })
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load history');
                    return response.text();
                })
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(error => {
                    if (error.name === 'AbortError') return;
                    console.error('History reload failed:', error);
                });
            }

            function handleScanError(message) {
                const input = document.getElementById('qrInput');
                const statusLabel = document.getElementById('scanStatusLabel');
                
                if (input) {
                    input.disabled = false;
                    input.placeholder = message || 'Error! Please try again.';
                    input.style.display = 'block';
                    input.focus();
                    setTimeout(() => {
                        input.placeholder = 'WAITING TO SCAN...';
                    }, 3000);
                }
                
                if (statusLabel) {
                    statusLabel.style.display = 'none';
                }
            }

            function processQRCode(studentId) {
                const input = document.getElementById('qrInput');
                if (!input) return;

                if (scanController) {
                    scanController.abort();
                }

                scanController = new AbortController();

                input.value = '';
                input.placeholder = 'PROCESSING...';
                input.disabled = true;

                const seq = ++scanSeq;

                fetch(scanUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        code: attendanceCode,
                        student_id: studentId,
                        qr_data: studentId
                    }),
                    signal: scanController.signal
                })
                .then(response => response.json())
                .then(data => {
                    if (seq !== scanSeq) return;
                    if (data.success) {
                        showStudentPreview({ student: data.student, attendance: data.attendance });
                        reloadAttendanceHistory();
                    } else {
                        handleScanError(data.message || 'Scan failed');
                    }
                })
                .catch(error => {
                    if (error.name === 'AbortError') return;
                    console.error('Scan error:', error);
                    handleScanError('Error! Please try again.');
                })
                .finally(() => {
                    if (input) {
                        input.disabled = false;
                    }
                });
            }

            function attachQRInputListeners() {
                const qrInput = document.getElementById('qrInput');
                if (!qrInput) return;

                qrInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const value = e.target.value.trim();
                        if (value) {
                            processQRCode(value);
                            e.target.value = '';
                        }
                    }
                });
            }

            function maintainQRFocus() {
                const input = document.getElementById('qrInput');
                if (input && document.activeElement !== input && input.style.display !== 'none') {
                    input.focus();
                }
            }

            attachQRInputListeners();
            setInterval(maintainQRFocus, 500);
            reloadAttendanceHistory();
            setInterval(reloadAttendanceHistory, 4000);

            @if($currentStudent)
                showStudentPreview({
                    student: {
                        id: {{ $currentStudent->id }},
                        name: @json($currentStudent->name),
                        section: { name: @json($currentStudent->section->name ?? '---') },
                        picture: @json($currentStudent->picture ? asset('storage/student_pictures/' . $currentStudent->picture) : null)
                    },
                    attendance: {
                        time_in_am: @json($currentAttendanceRecord->time_in_am ?? null),
                        time_out_am: @json($currentAttendanceRecord->time_out_am ?? null),
                        time_in_pm: @json($currentAttendanceRecord->time_in_pm ?? null),
                        time_out_pm: @json($currentAttendanceRecord->time_out_pm ?? null)
                    }
                });
            @endif
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>