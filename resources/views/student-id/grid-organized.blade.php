<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student ID Cards - Organized</title>
    <style>
        body {
            margin: 0;
            padding: 5mm;
            font-family: Arial, sans-serif;
            font-size: 10px;
            background: #fff;
        }
        
        /* Header Styles */
        .section-header {
            margin-top: 8mm;
            margin-bottom: 4mm;
            page-break-before: auto;
            page-break-after: avoid;
            break-after: avoid;
        }
        
        .section-header:first-child {
            margin-top: 0;
        }
        
        .school-header {
            font-size: 5mm;
            font-weight: bold;
            color: #000;
            margin-bottom: 1mm;
        }
        
        .school-year-header {
            margin-top: 2mm;
            margin-bottom: 3mm;
            page-break-after: avoid;
            break-after: avoid;
        }
        
        .school-year-text {
            font-size: 4mm;
            font-weight: normal;
            color: #000;
        }
        
        .teacher-header {
            margin-top: 2mm;
            margin-bottom: 2mm;
            page-break-after: avoid;
            break-after: avoid;
        }
        
        .teacher-text {
            font-size: 3.5mm;
            font-weight: normal;
            color: #000;
        }
        
        /* Grid Container */
        .page {
            width: 100%;
            max-width: 287mm;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3mm;
            justify-items: center;
            align-items: start;
            max-width: 287mm;
            margin-bottom: 5mm;
        }
        
        /* Student ID Card */
        .student-id {
            width: 85.6mm;
            height: 54mm;
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
            width: 35mm;
            text-align: center;
            vertical-align: top;
            padding: 2mm;
        }
        
        .qr-section {
            width: 30mm;
            height: 30mm;
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
            padding: 2mm 3mm;
            vertical-align: top;
        }
        
        .student-name {
            font-weight: bold;
            font-size: 3.5mm;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 1mm;
            line-height: 1.1;
            word-wrap: break-word;
        }
        
        .info-line {
            font-size: 2.8mm;
            color: #333;
            line-height: 1.3;
            margin-bottom: 0.5mm;
            word-wrap: break-word;
        }
        
        .student-id-number {
            font-weight: bold;
            font-size: 3mm;
            color: #0066cc;
            margin-bottom: 1mm;
        }
        
        .school-info {
            font-size: 2.5mm;
            color: #666;
            font-style: italic;
        }

        @media print {
            @page {
                size: A4;
                margin: 5mm;
            }
            
            body {
                padding: 0;
            }
            
            .section-header,
            .school-year-header,
            .teacher-header {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            
            .grid-container {
                page-break-inside: auto;
            }
            
            .student-id {
                page-break-inside: avoid;
            }
            
            /* Print button hide */
            .print-btn {
                display: none;
            }
        }
        
        /* Print button */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #4c51bf;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .print-btn:hover {
            background: #434190;
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Student IDs</button>
    
    <div class="page">
        @foreach($organized as $schoolKey => $school)
            {{-- School Name Header --}}
            <div class="section-header">
                <div class="school-header">{{ $school['name'] }}</div>
            </div>
            
            @foreach($school['school_years'] as $yearKey => $schoolYear)
                {{-- School Year Header --}}
                <div class="school-year-header">
                    <div class="school-year-text">School Year: {{ $schoolYear['name'] }}</div>
                </div>
                
                @foreach($schoolYear['teachers'] as $teacherKey => $teacher)
                    {{-- Teacher Name Header --}}
                    <div class="teacher-header">
                        <div class="teacher-text">Teacher: {{ $teacher['name'] }}</div>
                    </div>
                    
                    {{-- Student ID Cards Grid --}}
                    <div class="grid-container">
                        @foreach($teacher['students'] as $student)
                            <div class="student-id">
                                <table class="student-table">
                                    <tr>
                                        <td class="qr-cell">
                                            <div class="qr-section">
                                                @php
                                                    $qrCodeUrl = null;
                                                    if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                                                        $qrCodeUrl = url('/public-storage/' . ltrim($student->qr_code, '/'));
                                                    } else {
                                                        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                                                        $svgPath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg';
                                                        $pngPath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png';
                                                        
                                                        if (Storage::disk('public')->exists($svgPath)) {
                                                            $qrCodeUrl = url('/public-storage/' . ltrim($svgPath, '/'));
                                                        } elseif (Storage::disk('public')->exists($pngPath)) {
                                                            $qrCodeUrl = url('/public-storage/' . ltrim($pngPath, '/'));
                                                        }
                                                    }
                                                @endphp
                                                @if($qrCodeUrl)
                                                    <img src="{{ $qrCodeUrl }}" style="width: 28mm; height: 28mm;" alt="QR Code">
                                                @else
                                                    <div style="color: #999; font-size: 2.5mm; text-align: center;">No QR Code</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="info-cell">
                                            <div class="student-name">{{ $student->name }}</div>
                                            <div class="student-id-number">LRN: {{ $student->id_no }}</div>
                                            <div class="info-line">
                                                @if($student->section)
                                                    Grade {{ $student->section->gradelevel }} - {{ $student->section->name }}
                                                @else
                                                    No Section
                                                @endif
                                            </div>
                                            <div class="info-line">{{ $student->gender == 'M' ? 'Male' : 'Female' }} | Age: {{ $student->age }}</div>
                                            <div class="school-info">{{ $student->school->name ?? 'No School' }}</div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endforeach
        @endforeach
    </div>
</body>
</html>
