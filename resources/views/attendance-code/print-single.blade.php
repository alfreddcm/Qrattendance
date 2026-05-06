<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Access Code</title>
    <style>
        body {
            margin: 0;
            padding: 10mm;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            display: inline-block;
        }
        .attendance-code-card {
            width: 150mm;
            min-height: 100mm;
            border: 2px solid #0d6efd;
            background: #fff;
            padding: 10mm;
            margin: 0;
            box-sizing: border-box;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 5mm;
            margin-bottom: 5mm;
        }
        .school-logo {
            max-width: 40mm;
            max-height: 30mm;
            margin-bottom: 3mm;
        }
        .school-name {
            font-size: 6mm;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 2mm;
        }
        .card-title {
            font-size: 5mm;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 10mm;
            margin-bottom: 10mm;
            gap: 5mm;
        }
        .qr-section {
            text-align: center;
            padding: 5mm;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qr-code {
            width: 60mm;
            height: 60mm;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            margin: 0 auto;
        }
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .qr-label {
            font-size: 3.5mm;
            color: #666;
            margin-top: 2mm;
            font-weight: 600;
        }
        .code-section {
            text-align: center;
            padding: 4mm;
        }
        .code-label {
            font-size: 4mm;
            color: #666;
            margin-bottom: 2mm;
            text-transform: uppercase;
            font-weight: 600;
        }
        .code-value {
            font-size: 8mm;
            font-weight: bold;
            color: #0d6efd;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            line-height: 1.2;
        }
        .footer {
            margin-top: 5mm;
            text-align: center;
            font-size: 3mm;
            color: #999;
        }
        .status-badge {
            display: inline-block;
            padding: 1mm 3mm;
            background: #28a745;
            color: white;
            border-radius: 3mm;
            font-size: 3mm;
            font-weight: bold;
            margin-left: 3mm;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }
            .attendance-code-card {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="attendance-code-card">
            <div class="header">
                @if($attendanceCode->teacher->school && $attendanceCode->teacher->school->logo)
                    <img src="{{ url('/public-storage/' . ltrim($attendanceCode->teacher->school->logo, '/')) }}"
                         alt="School Logo"
                         class="school-logo">
                @endif
                <div class="school-name">
                    {{ $attendanceCode->teacher->school->name ?? 'School Name' }}
                </div>
                <div class="card-title">
                    Attendance Access Code
                 </div>
            </div>

            <div class="content">
                 <div class="qr-section">
                    <div class="qr-code">
                        @if($attendanceCode->qr_code_path)
                            @php
                                // Generate the URL for the QR code
                                // First try direct public path
                                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attendanceCode->qr_code_path)) {
                                    $qrUrl = url('/public-storage/' . ltrim($attendanceCode->qr_code_path, '/'));
                                } else {
                                    $qrUrl = url('/public-storage/' . ltrim($attendanceCode->qr_code_path, '/'));
                                }
                            @endphp
                            <img src="{{ $qrUrl }}" alt="QR Code">
                        @else
                            <div style="color: #999; font-size: 4mm; text-align: center;">
                                <div style="font-size: 12mm;">📷</div>
                                QR Code<br>Not Available
                            </div>
                        @endif
                    </div>
                    <div class="qr-label">Scan to Access</div>
                </div>

                 <div class="code-section">
                    <div class="code-label">6-Digit Access Code</div>
                    <div class="code-value">{{ $attendanceCode->code }}</div>
                </div>
            </div>

            <div class="footer">

                Date: {{ now()->format('F d, Y') }}<br>
             </div>
        </div>
    </div>

    <script>
         window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
