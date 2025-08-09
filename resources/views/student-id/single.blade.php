<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student ID Card</title>
    <style>
        body {
            margin: 0;
            padding: 10mm;
            font-family: Arial, sans-serif;
            font-size: 10px;
            background: #f5f5f5;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            display: inline-block;
        }
        .student-id {
            width: 85.6mm; /* 3.375 inches = 85.6mm */
            height: 54mm; /* 2.125 inches = 54mm */
            border: 1px solid #333;
            background: #fff;
            padding: 0;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .student-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .student-table td {
            vertical-align: middle;
            padding: 1mm;
            border: none;
        }
        .qr-cell {
            width: 28mm;
            text-align: center;
        }
        .qr-section {
            width: 22mm;
            height: 22mm;
            border: 1px solid #90EE90;
            background: #f8f9fa;
            margin: 0 auto;
            text-align: center;
            vertical-align: middle;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .info-cell {
            width: auto;
            padding-left: 3mm;
        }
        .student-name {
            font-weight: bold;
            font-size: 3mm;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 0.5mm;
            line-height: 1.1;
        }
        .info-line {
            font-size: 2.5mm;
            color: #333;
            line-height: 1.2;
            margin-bottom: 0.2mm;
        }
        
        @media print {
            @page {
                size: A4 landscape;
                margin: 5mm;
            }
            body {
                margin: 0;
                padding: 0;
                background: #fff;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .student-id {
                width: 85.6mm !important; /* 3.375 inches */
                height: 54mm !important; /* 2.125 inches */
                box-shadow: none;
                border: 1px solid #000;
            }
            .qr-section {
                border: 1px solid #666;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="student-id">
            <table class="student-table">
                <tr>
                    <td class="qr-cell">
                        <div class="qr-section">
                            @php
                                // Check if student has QR code in database
                                $qrImageContent = '';
                                $hasQrCode = false;
                                
                                if ($student->qr_code && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->qr_code)) {
                                    $qrPath = storage_path('app/public/' . $student->qr_code);
                                    if (file_exists($qrPath)) {
                                        $qrImageContent = base64_encode(file_get_contents($qrPath));
                                        $hasQrCode = true;
                                    }
                                } else {
                                    // Fallback to legacy filename pattern
                                    $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                                    $qrPath = public_path('storage/qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg');
                                    if (file_exists($qrPath)) {
                                        $qrImageContent = base64_encode(file_get_contents($qrPath));
                                        $hasQrCode = true;
                                    }
                                }
                            @endphp
                            @if($hasQrCode)
                                <img src="data:image/svg+xml;base64,{{ $qrImageContent }}" alt="QR Code" style="width: 20mm; height: 20mm; display: block; margin: 0 auto;">
                            @endif
                        </div>
                    </td>
                    <td class="info-cell">
                        <div class="info-line">Student ID: {{ $student->id_no }}</div>
                        <div class="student-name">{{ $student->name }}</div>
                        <div class="info-line">School: {{ $student->school->name ?? 'SGVS' }}</div>
                        <div class="info-line">Section: {{ $student->user->section_name ?? 'N/A' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
