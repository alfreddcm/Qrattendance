<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student ID Cards</title>
    <style>
        body {
            margin: 0;
            padding: 5mm;
            font-family: Arial, sans-serif;
            font-size: 10px;
            background: #fff;
        }
        .page {
            width: 100%;
            max-width: 287mm; /* A4 landscape width (297mm - 10mm margins) */
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 columns to fit more */
            gap: 3mm; /* Minimal gap */
            justify-items: center;
            align-items: start;
            max-width: 287mm;
        }
        .student-id {
            width: 85.6mm; /* 3.375 inches = 85.6mm */
            height: 54mm; /* 2.125 inches = 54mm */
            border: 1px solid #333;
            background: #fff;
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            break-inside: avoid;
            page-break-inside: avoid;
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
            width: 28mm; /* Slightly smaller for more text space */
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
            padding-left: 3mm; /* Reduced padding */
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
                margin: 5mm; /* Minimal margins */
            }
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }
            .page {
                max-width: 287mm; /* A4 landscape width minus minimal margins */
            }
            .grid-container {
                display: grid;
                grid-template-columns: repeat(3, 1fr); /* 3 columns */
                gap: 2mm; /* Minimal gap for printing */
                justify-items: center;
                align-items: start;
            }
            .student-id {
                width: 85.6mm !important; /* 3.375 inches */
                height: 54mm !important; /* 2.125 inches */
                margin: 0;
                box-shadow: none;
                break-inside: avoid;
                page-break-inside: avoid;
                border: 1px solid #000;
            }
            .qr-section {
                border: 1px solid #666;
            }
        }
        .page-break {
            break-after: page;
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="grid-container">
            @foreach($students as $index => $student)
            <div class="student-id {{ ($index + 1) % 9 == 0 ? 'page-break' : '' }}">
                <table class="student-table">
                    <tr>
                        <td class="qr-cell">
                            <div class="qr-section">
                                @php
                                    $qrImageContent = '';
                                    $hasQrCode = false;
                                    
                                    if ($student->qr_code && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->qr_code)) {
                                        $qrPath = storage_path('app/public/' . $student->qr_code);
                                        if (file_exists($qrPath)) {
                                            $qrImageContent = base64_encode(file_get_contents($qrPath));
                                            $hasQrCode = true;
                                        }
                                    } else {
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
            @endforeach
        </div>
    </div>
</body>
</html>
