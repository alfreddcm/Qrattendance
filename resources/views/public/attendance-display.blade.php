<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $school->name ?? 'School' }} - Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        html, body {
            height: 100vh;
            overflow: hidden;
        }

        body {
            background: #e8eef3;
            padding: 4px;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border-bottom: 3px solid #2196F3;
            padding: 6px 15px;
            margin-bottom: 4px;
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .school-logo {
            width: 45px;
            height: 45px;
            background: #2196F3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .school-info strong {
            display: block;
            font-size: 13px;
            color: #000;
            font-weight: 600;
            line-height: 1.2;
        }

        .school-info small {
            font-size: 10px;
            color: #666;
            line-height: 1.2;
        }

        .header-title {
            text-align: center;
            flex: 1;
        }

        .header-title h1 {
            font-size: 18px;
            color: #2196F3;
            font-weight: 600;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 340px 280px 280px;
            gap: 0;
            margin-bottom: 4px;
            flex: 1;
            min-height: 0;
        }

        .photo-section {
            background: #fff;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            aspect-ratio: 1 / 1;
            width: 100%;
        }

        .photo-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder-photo {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .placeholder-icon {
            font-size: 80px;
            color: #666;
        }

        .middle-section {
            background: #fff;
            border: 2px solid #ddd;
            padding: 6px;
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-size: 16px;
            font-weight: 700;
            color: #000;
            text-align: center;
            margin-bottom: 1px;
            line-height: 1.1;
        }

        .student-section {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-bottom: 4px;
            padding-bottom: 4px;
            border-bottom: 2px solid #ddd;
            line-height: 1.1;
        }

        .attendance-alert {
            background: #E3F2FD;
            border: 2px solid #2196F3;
            border-radius: 4px;
            padding: 6px;
            text-align: center;
            margin-bottom: 4px;
            display: none;
        }

        .attendance-alert.show {
            display: block;
        }

        .attendance-alert h2 {
            font-size: 13px;
            color: #000;
            margin-bottom: 4px;
        }

        .attendance-alert .time {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
            margin: 3px 0;
        }

        .attendance-alert .date {
            font-size: 11px;
            color: #666;
        }

        .record-table {
            width: 100%;
            border-collapse: collapse;
        }

        .record-table th {
            background: #2196F3;
            color: white;
            padding: 4px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
        }

        .record-table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
            font-size: 11px;
        }

        .record-table td:first-child {
            background: #f5f5f5;
            font-weight: 600;
            text-align: left;
            padding-left: 8px;
        }

        .right-section {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-display {
            background: #fff;
            border: 2px solid #ddd;
            padding: 10px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: #000;
            line-height: 1.2;
        }

        .time-display {
            background: #2196F3;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            border-radius: 4px;
        }

        .scan-buttons {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .scan-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .scan-btn:hover {
            background: #45a049;
        }

        .scan-btn.camera {
            background: #FF9800;
        }

        .scan-btn.camera:hover {
            background: #F57C00;
        }

        .scan-status {
            background: #fff;
            border: 2px solid #2196F3;
            padding: 10px;
            text-align: center;
            font-size: 15px;
            font-weight: 600;
            color: #2196F3;
            border-radius: 4px;
            width: 100%;
            outline: none;
        }

        .scan-status:focus {
            border-color: #1976D2;
            box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
        }

        .summary-box {
            background: #fff;
            border: 2px solid #ddd;
            padding: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .summary-box h3 {
            background: #2196F3;
            color: white;
            padding: 6px;
            text-align: center;
            font-size: 14px;
            margin: 0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            padding: 8px;
        }

        .summary-item {
            text-align: center;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .summary-item .label {
            font-size: 11px;
            color: #666;
            margin-bottom: 4px;
        }

        .summary-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #2196F3;
        }

        .recent-logs {
            background: #fff;
            border: 2px solid #ddd;
            padding: 0;
            flex-shrink: 0;
        }

        .recent-logs h3 {
            background: #2196F3;
            color: white;
            padding: 6px;
            text-align: center;
            font-size: 14px;
            margin: 0;
        }

        .logs-grid {
            display: flex;
            gap: 6px;
            padding: 8px;
            justify-content: flex-start;
        }

        .log-card {
            border: 2px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
            width: calc(20% - 5px);
            flex-shrink: 0;
        }

        .log-card .time-badge {
            background: #2196F3;
            color: white;
            padding: 4px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
        }

        .log-card .photo {
            width: 100%;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
        }
            background: #f5f5f5;
        }

        .log-card .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .log-card .placeholder-small {
            font-size: 50px;
            color: #666;
        }

        .log-card .info {
            background: #2196F3;
            color: white;
            padding: 5px;
            text-align: center;
        }

        .log-card .info .name {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .log-card .info .section {
            font-size: 10px;
        }

        #reader {
            display: none;
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
        }

        #usbInput {
            position: absolute;
            left: -9999px;
            width: 1px;
            height: 1px;
        }
    </style>
</head>
<body>

    <input type="text" id="usbInput" autocomplete="off">


    <div class="header">
        <div class="header-left">
            <div class="school-logo">{{ strtoupper(substr($school->name ?? 'S', 0, 1)) }}</div>
            <div class="school-info">
                <strong>{{ $school->name ?? 'School Name' }}</strong>
                <small>{{ $school->address ?? 'School Address' }}</small>
            </div>
        </div>
        <div class="header-title">
            <h1>Scan-to-Notify: A QR-Based Student Attendance and Parent Notification System</h1>
        </div>
    </div>


    <div class="main-grid">

        <div class="photo-section" id="photoSection">
            <div class="placeholder-photo">
                <i class="fas fa-user placeholder-icon"></i>
            </div>
        </div>


        <div class="middle-section">
            <div class="student-name" id="studentName">Student Name</div>
            <div class="student-section" id="studentSection">Grade and Section</div>


            <div class="attendance-alert" id="attendanceAlert">
                <h2>ATTENDANCE RECORDED!</h2>
                <div class="time" id="alertTime">TIME IN 7:30 AM</div>
                <div class="date" id="alertDate">September 10, 2025</div>
            </div>


            <table class="record-table">
                <tr>
                    <th colspan="2">Attendance Record</th>
                </tr>
                <tr>
                    <td>Morning In</td>
                    <td id="morningIn">--:--</td>
                </tr>
                <tr>
                    <td>Morning  Out</td>
                    <td id="morningOut">--:--</td>
                </tr>
                <tr>
                    <td>Afternoon In</td>
                    <td id="afternoonIn">--:--</td>
                </tr>
                <tr>
                    <td>Afternoon Out</td>
                    <td id="afternoonOut">--:--</td>
                </tr>
            </table>
        </div>


        <div class="right-section">
            <div class="date-display" id="dateDisplay">TODAY IS: MON, OCT 20, 2025</div>
            <div class="time-display" id="timeDisplay">9:56:28 AM</div>

            <div class="scan-buttons">
                <button class="scan-btn" id="usbScanBtn">
                    <i class="fas fa-barcode"></i> SCAN USING USB SCANNER
                </button>

            </div>

            <input type="text" class="scan-status" id="scanStatus" value="WAITING TO SCAN.." readonly>


            <div class="summary-box">
                <h3>Todays Attendance</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="label">Morning In</div>
                        <div class="value" id="summaryMorningIn">{{ $todaySummary['morning_in'] ?? 0 }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Morning  Out</div>
                        <div class="value" id="summaryMorningOut">{{ $todaySummary['morning_out'] ?? 0 }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Afternoon In</div>
                        <div class="value" id="summaryAfternoonIn">{{ $todaySummary['afternoon_in'] ?? 0 }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Afternoon Out</div>
                        <div class="value" id="summaryAfternoonOut">{{ $todaySummary['afternoon_out'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="reader"></div>


    <div class="recent-logs">
        <h3>Recent Attendance Logs</h3>
        <div class="logs-grid" id="logsGrid">
            @php
                $logCount = $recentAttendance->count();
                $placeholdersNeeded = 5 - $logCount;
            @endphp

            @foreach($recentAttendance as $log)
                <div class="log-card">
                    <div class="time-badge">
                        @php
                            $lastTime = $log->time_in_pm ?? $log->time_out_am ?? $log->time_in_am ?? $log->time_out_pm;
                            $timeType = 'TIME IN';
                            if ($log->time_out_pm) $timeType = 'TIME OUT';
                            elseif ($log->time_in_pm) $timeType = 'TIME IN';
                            elseif ($log->time_out_am) $timeType = 'TIME OUT';
                        @endphp
                        {{ $timeType }}: {{ $lastTime ? \Carbon\Carbon::parse($lastTime)->format('g:i A') : '--:--' }}
                    </div>
                    <div class="photo">
                        @if($log->student->picture)
                            <img src="{{ asset('storage/' . $log->student->picture) }}" alt="{{ $log->student->name }}">
                        @else
                            <i class="fas fa-user placeholder-small"></i>
                        @endif
                    </div>
                    <div class="info">
                        <div class="name">{{ $log->student->name }}</div>
                        <div class="section">{{ $log->student->section->name ?? 'N/A' }}</div>
                    </div>
                </div>
            @endforeach

            @for($i = 0; $i < $placeholdersNeeded; $i++)
                <div class="log-card">
                    <div class="time-badge">--:--</div>
                    <div class="photo">
                        <i class="fas fa-user placeholder-small"></i>
                    </div>
                    <div class="info">
                        <div class="name">Name</div>
                        <div class="section">Grade Section</div>
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <script>
        const code = '{{ $code }}';
        let html5QrCode = null;
        let isScanning = false;

        // Auto-focus USB input
        document.getElementById('usbInput').focus();

        // Update Date and Time
        function updateDateTime() {
            const now = new Date();

            // Format: TODAY IS: MON, OCT 20, 2025
            const days = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
            const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
            const dateStr = `TODAY IS: ${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
            document.getElementById('dateDisplay').textContent = dateStr;

            // Format: 9:56:28 AM
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            const timeStr = `${hours}:${minutes}:${seconds} ${ampm}`;
            document.getElementById('timeDisplay').textContent = timeStr;
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

        // USB Scanner Handler
        document.getElementById('usbInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const studentId = this.value.trim();
                if (studentId) {
                    processQRScan(studentId);
                    this.value = '';
                }
            }
        });

        // USB Scan Button
        document.getElementById('usbScanBtn').addEventListener('click', function() {
            document.getElementById('usbInput').focus();
            document.getElementById('scanStatus').value = 'READY FOR USB SCAN...';
        });

        // Camera Scan Button
        document.getElementById('cameraScanBtn').addEventListener('click', function() {
            toggleCamera();
        });

        function toggleCamera() {
            const readerDiv = document.getElementById('reader');

            if (isScanning) {
                html5QrCode.stop().then(() => {
                    readerDiv.style.display = 'none';
                    isScanning = false;
                    document.getElementById('scanStatus').value = 'WAITING TO SCAN..';
                }).catch(err => {
                    console.error('Error stopping camera:', err);
                });
            } else {
                readerDiv.style.display = 'block';
                html5QrCode = new Html5Qrcode("reader");

                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: 250 },
                    (decodedText) => {
                        processQRScan(decodedText);
                        html5QrCode.stop();
                        readerDiv.style.display = 'none';
                        isScanning = false;
                    }
                ).then(() => {
                    isScanning = true;
                    document.getElementById('scanStatus').value = 'SCANNING...';
                }).catch(err => {
                    alert('Unable to access camera: ' + err);
                    readerDiv.style.display = 'none';
                });
            }
        }

        // Process QR Scan
        function processQRScan(studentId) {
            document.getElementById('scanStatus').value = studentId;

            fetch(`/public/attendance/scan-qr`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    code: code,
                    student_id: studentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAttendanceRecord(data);
                    updateRecentLogs();
                    updateSummary();
                    document.getElementById('scanStatus').value = 'SCAN SUCCESSFUL! - ' + data.student.name;

                    setTimeout(() => {
                        document.getElementById('scanStatus').value = 'WAITING TO SCAN..';
                        document.getElementById('usbInput').focus();
                    }, 3000);
                } else {
                    document.getElementById('scanStatus').value = data.message || 'SCAN FAILED';
                    setTimeout(() => {
                        document.getElementById('scanStatus').value = 'WAITING TO SCAN..';
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('scanStatus').value = 'ERROR OCCURRED';
                setTimeout(() => {
                    document.getElementById('scanStatus').value = 'WAITING TO SCAN..';
                }, 3000);
            });
        }

        // Display Attendance Record
        function displayAttendanceRecord(data) {
            const student = data.student;
            const attendance = data.attendance;

            // Update photo
            const photoSection = document.getElementById('photoSection');
            if (student.picture) {
                photoSection.innerHTML = `<img src="${student.picture}" alt="${student.name}">`;
            } else {
                photoSection.innerHTML = `
                    <div class="placeholder-photo">
                        <i class="fas fa-user placeholder-icon"></i>
                    </div>
                `;
            }

            // Update student info
            document.getElementById('studentName').textContent = student.name;
            document.getElementById('studentSection').textContent = student.section;

            // Show attendance alert
            const alert = document.getElementById('attendanceAlert');
            const typeLabels = {
                'time_in_am': 'TIME IN',
                'time_out_am': 'TIME OUT',
                'time_in_pm': 'TIME IN',
                'time_out_pm': 'TIME OUT'
            };
            document.getElementById('alertTime').textContent = `${typeLabels[attendance.type] || 'TIME IN'} ${attendance.recorded_time}`;
            document.getElementById('alertDate').textContent = attendance.recorded_date;
            alert.classList.add('show');

            // Update attendance record table
            document.getElementById('morningIn').textContent = attendance.am_in || '--:--';
            document.getElementById('morningOut').textContent = attendance.am_out || '--:--';
            document.getElementById('afternoonIn').textContent = attendance.pm_in || '--:--';
            document.getElementById('afternoonOut').textContent = attendance.pm_out || '--:--';

            // Hide alert after 5 seconds
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
        }

        // Update Recent Logs
        function updateRecentLogs() {
            fetch(`/public/attendance/${code}/recent-logs`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const logsGrid = document.getElementById('logsGrid');
                        const logs = data.data;
                        const placeholdersNeeded = 5 - logs.length;

                        let html = '';

                        logs.forEach(log => {
                            const photoHtml = log.photo.includes('default-avatar')
                                ? '<i class="fas fa-user placeholder-small"></i>'
                                : `<img src="${log.photo}" alt="${log.student_name}">`;

                            html += `
                                <div class="log-card">
                                    <div class="time-badge">${log.time_type}: ${log.time_in}</div>
                                    <div class="photo">${photoHtml}</div>
                                    <div class="info">
                                        <div class="name">${log.student_name}</div>
                                        <div class="section">${log.section}</div>
                                    </div>
                                </div>
                            `;
                        });

                        for (let i = 0; i < placeholdersNeeded; i++) {
                            html += `
                                <div class="log-card">
                                    <div class="time-badge">--:--</div>
                                    <div class="photo">
                                        <i class="fas fa-user placeholder-small"></i>
                                    </div>
                                    <div class="info">
                                        <div class="name">Name</div>
                                        <div class="section">Grade Section</div>
                                    </div>
                                </div>
                            `;
                        }

                        logsGrid.innerHTML = html;
                    }
                })
                .catch(error => console.error('Error updating logs:', error));
        }

        // Update Summary
        function updateSummary() {
            fetch(`/public/attendance/${code}/summary`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('summaryMorningIn').textContent = data.data.morning_in || 0;
                        document.getElementById('summaryMorningOut').textContent = data.data.morning_out || 0;
                        document.getElementById('summaryAfternoonIn').textContent = data.data.afternoon_in || 0;
                        document.getElementById('summaryAfternoonOut').textContent = data.data.afternoon_out || 0;
                    }
                })
                .catch(error => console.error('Error updating summary:', error));
        }

        // Auto-refresh logs and summary
        setInterval(updateRecentLogs, 10000); // Every 10 seconds
        setInterval(updateSummary, 15000); // Every 15 seconds

        // Keep USB input focused
        setInterval(() => {
            if (document.activeElement !== document.getElementById('usbInput') && !isScanning) {
                document.getElementById('usbInput').focus();
            }
        }, 1000);
    </script>
</body>
</html>
