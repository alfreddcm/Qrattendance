<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCode;
use App\Models\Student;
use App\Models\Section;
use App\Models\SchoolYear;
use App\Models\Attendance;
use App\Models\OutboundMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicAttendanceController extends Controller
{
    public function show($code)
    {
        $attendanceCode = AttendanceCode::with(['teacher.school', 'section'])
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$attendanceCode) {
            return view('public.attendance-error', [
                'message' => 'Invalid or Expired QR Code',
                'details' => 'The attendance code you scanned is not valid or has expired.'
            ]);
        }

        $school = $attendanceCode->teacher->school;
        $section = $attendanceCode->section;
        
        // Get all sections for students assigned to this teacher (user_id)
        $teacherSections = Student::where('user_id', $attendanceCode->teacher_id)
            ->with('section')
            ->get()
            ->pluck('section')
            ->filter()
            ->unique('id')
            ->values();
        
        // Initialize with null/empty values for placeholders (waiting to scan state)
        $currentStudent = null;
        $currentAttendanceRecord = null;
        
        // Check if there's a recently scanned student in session
        $sessionKey = 'scanned_student_' . $code;
        $scannedData = session($sessionKey);
        
        if ($scannedData && isset($scannedData['scan_time'])) {
            // Check if the scan was within last 5 seconds
            $scanTime = \Carbon\Carbon::parse($scannedData['scan_time']);
            $now = now();
            $secondsElapsed = abs($now->diffInRealSeconds($scanTime));
            
            \Log::info('Session check', [
                'scan_time' => $scannedData['scan_time'],
                'current_time' => $now->toDateTimeString(),
                'seconds_elapsed' => $secondsElapsed,
                'will_show' => $secondsElapsed <= 5
            ]);
            
            if ($secondsElapsed <= 5) {
                // Show the student data
                $currentStudent = Student::with('section')->find($scannedData['student_id']);
                $currentAttendanceRecord = Attendance::find($scannedData['attendance_id']);
            } else {
                // Clear the session data if expired
                session()->forget($sessionKey);
                \Log::info('Session expired and cleared', [
                    'seconds_elapsed' => $secondsElapsed
                ]);
            }
        }
        
        // Get recent attendance events for the gallery (last 7 scan events)
        $manilaToday = Carbon::now('Asia/Manila')->toDateString();
        $recentAttendance = $this->buildRecentAttendanceEvents($attendanceCode->teacher_id, $manilaToday, 7);

        // Get today's summary counts
        $todaySummary = $this->getTodayAttendanceSummary($attendanceCode->teacher_id);

        // Return JSON if requested via AJAX
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'currentStudent' => $currentStudent ? [
                    'id' => $currentStudent->id,
                    'name' => $currentStudent->name,
                    'stud_fname' => $currentStudent->stud_fname,
                    'stud_mname' => $currentStudent->stud_mname,
                    'stud_lname' => $currentStudent->stud_lname,
                    'stud_code' => $currentStudent->stud_code,
                    'qr_code' => $currentStudent->qr_code,
                    'section' => $currentStudent->section ? ['name' => $currentStudent->section->name] : null,
                ] : null,
                'currentAttendanceRecord' => $currentAttendanceRecord ? [
                    'time_in_am' => $currentAttendanceRecord->time_in_am,
                    'time_out_am' => $currentAttendanceRecord->time_out_am,
                    'time_in_pm' => $currentAttendanceRecord->time_in_pm,
                    'time_out_pm' => $currentAttendanceRecord->time_out_pm,
                ] : null,
                'recentAttendance' => $recentAttendance,
            ]);
        }

        return view('public.attendance-display', compact(
            'code',
            'school',
            'section',
            'teacherSections',
            'recentAttendance',
            'todaySummary',
            'attendanceCode',
            'currentStudent',
            'currentAttendanceRecord'
        ));
    }

    public function clearStudent($code)
    {
        $sessionKey = 'scanned_student_' . $code;
        session()->forget($sessionKey);
        
        return response()->json([
            'success' => true,
            'message' => 'Student data cleared'
        ]);
    }

    public function getRecentLogs(Request $request, $code)
    {
        $attendanceCode = AttendanceCode::where('code', $code)->where('is_active', true)->first();
        
        if (!$attendanceCode) {
            return response()->json(['success' => false, 'message' => 'Invalid code']);
        }

        $acceptHeader = $request->header('Accept');
        $asHtml = $request->query('format') === 'html' || ($acceptHeader && str_contains($acceptHeader, 'text/html'));

        // Use Manila timezone for accurate date comparison
        $manilaToday = Carbon::now('Asia/Manila')->toDateString();

        $recentAttendance = $this->buildRecentAttendanceEvents($attendanceCode->teacher_id, $manilaToday, 7);

        if ($asHtml) {
            // Prevent caching so the history always shows fresh data
            return response(view('public.partials.attendance-history', compact('recentAttendance')))
                           ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
                           ->header('Pragma', 'no-cache')
                           ->header('Expires', '0');
        }

        return response()->json(['success' => true, 'data' => $recentAttendance]);
    }

    private function buildRecentAttendanceEvents(int $teacherId, string $manilaDate, int $limit = 7)
    {
        $attendanceRows = Attendance::with(['student.section'])
            ->whereDate('date', $manilaDate)
            ->whereHas('student', function ($query) use ($teacherId) {
                $query->where('user_id', $teacherId);
            })
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get();

        $events = collect();

        foreach ($attendanceRows as $attendance) {
            $this->appendAttendanceEvent($events, $attendance, 'time_in_am', 'TIME IN');
            $this->appendAttendanceEvent($events, $attendance, 'time_out_am', 'TIME OUT');
            $this->appendAttendanceEvent($events, $attendance, 'time_in_pm', 'TIME IN');
            $this->appendAttendanceEvent($events, $attendance, 'time_out_pm', 'TIME OUT');
        }

        return $events
            ->sortByDesc('event_at')
            ->take($limit)
            ->values();
    }

    private function appendAttendanceEvent($events, Attendance $attendance, string $column, string $type): void
    {
        $rawTime = $attendance->{$column};
        if (empty($rawTime) || !$attendance->student) {
            return;
        }

        $isoTime = $this->toIsoDateTime($attendance->date, $rawTime);
        $displayTime = $this->toFormattedTime($attendance->date, $rawTime);

        if (!$isoTime || !$displayTime) {
            return;
        }

        $events->push([
            'id' => $attendance->id . '-' . $column,
            'display_type' => $type,
            'display_time' => $displayTime,
            'event_at' => $isoTime,
            'student' => [
                'id' => $attendance->student->id,
                'name' => $attendance->student->name ?? '---',
                'section' => [
                    'name' => $attendance->student->section->name ?? '---',
                ],
                'picture' => $attendance->student->picture
                    ? asset('storage/student_pictures/' . $attendance->student->picture)
                    : null,
            ],
        ]);
    }

    public function getTodaySummary($code)
    {
        $attendanceCode = AttendanceCode::where('code', $code)->where('is_active', true)->first();
        
        if (!$attendanceCode) {
            return response()->json(['success' => false, 'message' => 'Invalid code']);
        }

        $summary = $this->getTodayAttendanceSummary($attendanceCode->teacher_id);
        return response()->json(['success' => true, 'data' => $summary]);
    }

    private function getTodayAttendanceSummary($teacherId)
    {
        $today = Carbon::now('Asia/Manila')->startOfDay();
        
        \Log::info('Getting today attendance summary', [
            'teacher_id' => $teacherId,
            'date' => $today->toDateString()
        ]);
        
        $morningIn = Attendance::whereDate('date', $today)
            ->whereHas('student', function ($query) use ($teacherId) {
                $query->where('user_id', $teacherId);
            })
            ->whereNotNull('time_in_am')
            ->count();
            
        $morningOut = Attendance::whereDate('date', $today)
            ->whereHas('student', function ($query) use ($teacherId) {
                $query->where('user_id', $teacherId);
            })
            ->whereNotNull('time_out_am')
            ->count();
            
        $afternoonIn = Attendance::whereDate('date', $today)
            ->whereHas('student', function ($query) use ($teacherId) {
                $query->where('user_id', $teacherId);
            })
            ->whereNotNull('time_in_pm')
            ->count();
            
        $afternoonOut = Attendance::whereDate('date', $today)
            ->whereHas('student', function ($query) use ($teacherId) {
                $query->where('user_id', $teacherId);
            })
            ->whereNotNull('time_out_pm')
            ->count();
            
        \Log::info('Today attendance summary calculated', [
            'morning_in' => $morningIn,
            'morning_out' => $morningOut,
            'afternoon_in' => $afternoonIn,
            'afternoon_out' => $afternoonOut
        ]);

        return [
            'morning_in' => $morningIn,
            'morning_out' => $morningOut,
            'afternoon_in' => $afternoonIn,
            'afternoon_out' => $afternoonOut
        ];
    }

    public function index(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return view('public.attendance-login');
        }

        return redirect()->route('public.attendance.show', ['code' => $code]);
    }

    public function scanQR(Request $request)
    {
        try {
            $now = Carbon::now('Asia/Manila');

            $request->validate([
                'code' => 'nullable|string',
                'qr_data' => 'required|string',
                'student_id' => 'required|string',
            ]);

            $requestCode = trim((string) $request->input('code', ''));
            $attendanceCode = null;

            if ($requestCode !== '') {
                $attendanceCode = AttendanceCode::where('code', $requestCode)
                    ->where('is_active', true)
                    ->first();

                if (!$attendanceCode) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or inactive attendance code.'
                    ], 404);
                }
            } else {
                // Backward compatibility for old clients not yet sending code.
                $attendanceCode = AttendanceCode::where('is_active', true)->first();
            }

            Log::info('Public scan received', [
                'student_id' => $request->student_id,
                'qr_data' => $request->qr_data,
                'payload' => $request->all(),
                'timestamp' => $now->toIso8601String()
            ]);

            $scanValue = trim((string) ($request->student_id ?: $request->qr_data));

            Log::info('Public scan normalized payload', [
                'scan_value' => $scanValue,
                'scan_length' => strlen($scanValue),
            ]);

            // Find student by QR data and bind to current attendance code context
            $student = Student::with('section', 'schoolYear')
                ->where('user_id', $attendanceCode?->teacher_id)
                ->where(function ($query) use ($scanValue) {
                    $query->where('stud_code', $scanValue)
                        ->orWhere('id_no', $scanValue);
                })
                ->first();

            $fallbackIdNo = null;
            if (!$student && str_contains($scanValue, '_')) {
                $fallbackIdNo = explode('_', $scanValue, 2)[0];
                $student = Student::with('section', 'schoolYear')
                    ->where('user_id', $attendanceCode?->teacher_id)
                    ->where('id_no', $fallbackIdNo)
                    ->first();

                if ($student) {
                    Log::warning('Public scan fallback match by id_no prefix', [
                        'scan_value' => $scanValue,
                        'fallback_id_no' => $fallbackIdNo,
                        'matched_student_id' => $student->id,
                        'stored_stud_code' => $student->stud_code,
                    ]);
                }
            }

            if (!$student) {
                Log::warning('Public scan student not found', [
                    'scan_value' => $scanValue,
                    'fallback_id_no' => $fallbackIdNo,
                    'raw_student_id' => $request->student_id,
                    'raw_qr_data' => $request->qr_data,
                ]);

                $response = [
                    'success' => false,
                    'message' => 'Student not found on scan. Please check the QR code and try again.'
                ];

                if (config('app.debug')) {
                    $response['debug'] = [
                        'scan_value' => $scanValue,
                        'hint' => 'No student matched by stud_code or id_no',
                    ];
                }

                return response()->json([
                    ...$response
                ], 404);
            }

            if ($attendanceCode && $attendanceCode->section_id && (int) $student->section_id !== (int) $attendanceCode->section_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not assigned to the selected attendance section.'
                ], 403);
            }

            if (!$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not assigned to any section. Please contact your teacher.'
                ], 400);
            }

            if (!$student->schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not assigned to any school year.'
                ], 400);
            }

            $timeCheck = $this->validateAttendanceTimeWindow($student->section);
            if (!$timeCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $timeCheck['error']
                ], 400);
            }

            $timeDetection = $this->smartTimeDetection(
                $student->id,
                $student->school_year_id,
                null
            );

            if (!$timeDetection['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $timeDetection['error']
                ], 400);
            }

            Log::info('Public scan time detection', [
                'student_id' => $student->id,
                'time_detection' => $timeDetection,
                'now' => $now->toDateTimeString()
            ]);

            $status = $this->calculateAttendanceStatus(
                $student->section,
                $timeDetection['type'],
                $now
            );

            $attendance = $this->saveAttendanceRecord(
                $student->id,
                $student->school_year_id,
                $student->section_id,
                null,
                $timeDetection['column'],
                $timeDetection['status_column'],
                $status,
                $student->user_id,
                null,
                $now
            );

            Log::info('Attendance saved', [
                'attendance_id' => $attendance->id,
                'student_id' => $student->id,
                'column_updated' => $timeDetection['column'],
                'status_column' => $timeDetection['status_column'],
                'record_time' => $now->toDateTimeString(),
                'saved_values' => [
                    'time_in_am' => $attendance->time_in_am,
                    'time_out_am' => $attendance->time_out_am,
                    'time_in_pm' => $attendance->time_in_pm,
                    'time_out_pm' => $attendance->time_out_pm,
                    'am_status' => $attendance->am_status,
                    'pm_status' => $attendance->pm_status,
                ]
            ]);

            // Queue notification
            $this->queueAttendanceNotification(
                $student,
                $attendance,
                $timeDetection['type'],
                $student->user_id ?? 0
            );

            // Store in session for the exact display page code used by this scan
            if ($attendanceCode) {
                $sessionKey = 'scanned_student_' . $attendanceCode->code;
                session([
                    $sessionKey => [
                        'student_id' => $student->id,
                        'attendance_id' => $attendance->id,
                        'scan_time' => now()->toDateTimeString()
                    ]
                ]);
                
                \Log::info('Session stored for student', [
                    'session_key' => $sessionKey,
                    'student_id' => $student->id,
                    'attendance_id' => $attendance->id
                ]);
            }

            Log::info('Public QR attendance recorded', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'attendance_type' => $timeDetection['type'],
                'status' => $status
            ]);

            // Refresh to get exact stored values
            $attendance->refresh();

            return response()->json([
                'success' => true,
                'message' => $this->getAttendanceSuccessMessage($timeDetection['type'], $status),
                'student' => [
                    'id' => $student->id,
                    'id_no' => $student->id_no,
                    'name' => $student->name,
                    'picture' => $student->picture ? asset('storage/student_pictures/' . $student->picture) : null,
                    'section' => ['name' => $student->section->name],
                    'semester' => $student->schoolYear->name,
                ],
                'attendance' => [
                    'id' => $attendance->id,
                    'type' => $timeDetection['type'],
                    'period' => $timeDetection['period'],
                    'status' => $status,
                    'recorded_time' => $now->format('g:i A'),
                    'recorded_date' => $now->format('F d, Y'),
                    'time_in_am' => $this->toIsoDateTime($attendance->date, $attendance->time_in_am),
                    'time_out_am' => $this->toIsoDateTime($attendance->date, $attendance->time_out_am),
                    'time_in_pm' => $this->toIsoDateTime($attendance->date, $attendance->time_in_pm),
                    'time_out_pm' => $this->toIsoDateTime($attendance->date, $attendance->time_out_pm),
                    'time_in_am_formatted' => $this->toFormattedTime($attendance->date, $attendance->time_in_am),
                    'time_out_am_formatted' => $this->toFormattedTime($attendance->date, $attendance->time_out_am),
                    'time_in_pm_formatted' => $this->toFormattedTime($attendance->date, $attendance->time_in_pm),
                    'time_out_pm_formatted' => $this->toFormattedTime($attendance->date, $attendance->time_out_pm),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in public QR attendance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function record(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|size:6',
                'student_id' => 'required|exists:students,id',
                'attendance_type' => 'required|in:time_in_am,time_out_am,time_in_pm,time_out_pm',
            ]);

            $attendanceCode = AttendanceCode::where('code', $validated['code'])
                ->where('is_active', true)
                ->first();

            if (!$attendanceCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired code.'
                ], 403);
            }

            $student = Student::with('section', 'schoolYear')->findOrFail($validated['student_id']);
            
            if ($attendanceCode->section_id && $student->section_id != $attendanceCode->section_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student does not belong to this section.'
                ], 403);
            }

            $status = $this->calculateAttendanceStatus(
                $student->section,
                $validated['attendance_type'],
                now()
            );

            $columnMap = [
                'time_in_am' => ['column' => 'time_in_am', 'status_column' => 'am_status'],
                'time_out_am' => ['column' => 'time_out_am', 'status_column' => 'am_status'],
                'time_in_pm' => ['column' => 'time_in_pm', 'status_column' => 'pm_status'],
                'time_out_pm' => ['column' => 'time_out_pm', 'status_column' => 'pm_status'],
            ];

            $mapping = $columnMap[$validated['attendance_type']];

            $attendance = $this->saveAttendanceRecord(
                $student->id,
                $student->school_year_id,
                $student->section_id,
                null,
                $mapping['column'],
                $mapping['status_column'],
                $status,
                $attendanceCode->teacher_id,
                'Recorded via public link',
                null
            );

            // Queue notification
            $this->queueAttendanceNotification(
                $student,
                $attendance,
                $validated['attendance_type'],
                $attendanceCode->teacher_id
            );

            Log::info('Attendance recorded via public page', [
                'code' => $validated['code'],
                'student_id' => $validated['student_id'],
                'attendance_type' => $validated['attendance_type'],
                'status' => $status
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->getAttendanceSuccessMessage($validated['attendance_type'], $status),
                'student' => [
                    'name' => $student->name,
                    'id_no' => $student->id_no,
                ],
                'recorded_time' => now()->format('h:i A'),
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error('Error recording public attendance', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    private function smartTimeDetection(int $studentId, int $schoolYearId, ?int $sessionId = null): array
    {
        $now = now();
        $today = $now->toDateString();
        $currentHour = $now->hour;
        
        $isAM = $currentHour < 12;
        $period = $isAM ? 'AM' : 'PM';

        $query = Attendance::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->whereDate('date', $today);
            
        if ($sessionId) {
            $query->where('attendance_session_id', $sessionId);
        }
        
        $todayAttendance = $query->first();

        if (!$todayAttendance) {
            if ($isAM) {
                return [
                    'valid' => true,
                    'type' => 'time_in_am',
                    'column' => 'time_in_am',
                    'status_column' => 'am_status',
                    'period' => 'AM',
                    'action' => 'Recording morning time in'
                ];
            } else {
                return [
                    'valid' => true,
                    'type' => 'time_in_pm',
                    'column' => 'time_in_pm',
                    'status_column' => 'pm_status',
                    'period' => 'PM',
                    'action' => 'Recording afternoon time in'
                ];
            }
        }

        if ($isAM) {
            if ($todayAttendance->time_in_am && !$todayAttendance->time_out_am) {
                $timeInAM = Carbon::parse($todayAttendance->time_in_am);
                if ($now->diffInMinutes($timeInAM) < 30) {
                    return [
                        'valid' => false,
                        'error' => 'Too early to record time out. Minimum 30 minutes required after time in.'
                    ];
                }

                return [
                    'valid' => true,
                    'type' => 'time_out_am',
                    'column' => 'time_out_am',
                    'status_column' => 'am_status',
                    'period' => 'AM',
                    'action' => 'Recording morning time out'
                ];
            }

            if ($todayAttendance->time_in_am && $todayAttendance->time_out_am) {
                return [
                    'valid' => false,
                    'error' => 'AM attendance already completed. Time in: ' . 
                              Carbon::parse($todayAttendance->time_in_am)->format('h:i A') . 
                              ', Time out: ' . 
                              Carbon::parse($todayAttendance->time_out_am)->format('h:i A')
                ];
            }

            return [
                'valid' => true,
                'type' => 'time_in_am',
                'column' => 'time_in_am',
                'status_column' => 'am_status',
                'period' => 'AM',
                'action' => 'Recording morning time in'
            ];
        }

        if (!$isAM) {
            if ($todayAttendance->time_in_pm && !$todayAttendance->time_out_pm) {
                $timeInPM = Carbon::parse($todayAttendance->time_in_pm);
                if ($now->diffInMinutes($timeInPM) < 30) {
                    return [
                        'valid' => false,
                        'error' => 'Too early to record time out. Minimum 30 minutes required after time in.'
                    ];
                }

                return [
                    'valid' => true,
                    'type' => 'time_out_pm',
                    'column' => 'time_out_pm',
                    'status_column' => 'pm_status',
                    'period' => 'PM',
                    'action' => 'Recording afternoon time out'
                ];
            }

            if ($todayAttendance->time_in_pm && $todayAttendance->time_out_pm) {
                return [
                    'valid' => false,
                    'error' => 'PM attendance already completed. Time in: ' . 
                              Carbon::parse($todayAttendance->time_in_pm)->format('h:i A') . 
                              ', Time out: ' . 
                              Carbon::parse($todayAttendance->time_out_pm)->format('h:i A')
                ];
            }

            return [
                'valid' => true,
                'type' => 'time_in_pm',
                'column' => 'time_in_pm',
                'status_column' => 'pm_status',
                'period' => 'PM',
                'action' => 'Recording afternoon time in'
            ];
        }

        return [
            'valid' => false,
            'error' => 'Unable to determine attendance type'
        ];
    }

    private function calculateAttendanceStatus(Section $section, string $attendanceType, Carbon $recordTime): string
    {
        if (str_contains($attendanceType, 'time_out')) {
            return 'On Time';
        }

        $gracePeriod = 10;
        
        if (str_contains($attendanceType, '_am')) {
            if (!$section->am_time_in_start) {
                return 'On Time';
            }
            
            $expectedTime = Carbon::parse($section->am_time_in_start);
            
            if ($recordTime->lessThan($expectedTime->copy()->subMinutes(15))) {
                return 'Early';
            }
            
            if ($recordTime->lessThanOrEqualTo($expectedTime->copy()->addMinutes($gracePeriod))) {
                return 'On Time';
            }
            
            if ($recordTime->lessThanOrEqualTo($expectedTime->copy()->addMinutes(30))) {
                return 'Tardy';
            }
            
            return 'Late';
        } else {
            if (!$section->pm_time_in_start) {
                return 'On Time';
            }
            
            $expectedTime = Carbon::parse($section->pm_time_in_start);
            
            if ($recordTime->lessThan($expectedTime->copy()->subMinutes(15))) {
                return 'Early';
            }
            
            if ($recordTime->lessThanOrEqualTo($expectedTime->copy()->addMinutes($gracePeriod))) {
                return 'On Time';
            }
            
            if ($recordTime->lessThanOrEqualTo($expectedTime->copy()->addMinutes(30))) {
                return 'Tardy';
            }
            
            return 'Late';
        }
    }

    private function saveAttendanceRecord(
        int $studentId,
        int $schoolYearId,
        int $sectionId,
        ?int $sessionId,
        string $columnToUpdate,
        string $statusColumn,
        string $status,
        ?int $teacherId = null,
        ?string $remarks = null,
        ?Carbon $overrideTime = null
    ): Attendance {
        $today = now()->toDateString();
        $recordTime = $overrideTime ?? Carbon::now('Asia/Manila');

        $attendanceData = [
            'student_id' => $studentId,
            'school_year_id' => $schoolYearId,
            'date' => $today,
        ];
        
        if ($sessionId) {
            $attendanceData['attendance_session_id'] = $sessionId;
        }
        
        $attendance = Attendance::firstOrNew($attendanceData);

        if (!$attendance->exists) {
            $attendance->school_id = Student::find($studentId)->school_id;
            $attendance->teacher_id = $teacherId;
        } elseif (!$attendance->teacher_id && $teacherId) {
            $attendance->teacher_id = $teacherId;
        }

        $attendance->$columnToUpdate = $recordTime;
        $attendance->$statusColumn = $status;
        
        if ($remarks) {
            $attendance->remarks = $remarks;
        } else {
            $attendance->remarks = $this->generateAttendanceRemarks($columnToUpdate, $status, $recordTime);
        }
        
        $attendance->save();

        return $attendance;
    }

    private function generateAttendanceRemarks(string $attendanceType, string $status, Carbon $recordedTime): string
    {
        $remarks = [];

        if (str_contains($attendanceType, 'time_in')) {
            $remarks[] = 'Scanned at ' . $recordedTime->format('h:i A');
            
            if ($status === 'Late' || $status === 'Tardy') {
                $period = str_contains($attendanceType, '_am') ? 'morning' : 'afternoon';
                $remarks[] = "{$status} for {$period} session";
            }
        } else {
            $remarks[] = 'Time out recorded at ' . $recordedTime->format('h:i A');
        }

        return implode(' | ', $remarks);
    }

    private function getAttendanceSuccessMessage(string $attendanceType, string $status): string
    {
        $messages = [
            'time_in_am' => "✓ Morning time in recorded - {$status}",
            'time_out_am' => '✓ Morning time out recorded successfully',
            'time_in_pm' => "✓ Afternoon time in recorded - {$status}",
            'time_out_pm' => '✓ Afternoon time out recorded successfully',
        ];

        return $messages[$attendanceType] ?? 'Attendance recorded';
    }

    private function toIsoDateTime($dateValue, $timeValue): ?string
    {
        $parsed = $this->parseAttendanceDateTime($dateValue, $timeValue);
        return $parsed ? $parsed->toIso8601String() : null;
    }

    private function toFormattedTime($dateValue, $timeValue): ?string
    {
        $parsed = $this->parseAttendanceDateTime($dateValue, $timeValue);
        return $parsed ? $parsed->format('g:i A') : null;
    }

    private function parseAttendanceDateTime($dateValue, $timeValue): ?Carbon
    {
        if (empty($timeValue)) {
            return null;
        }

        $timeText = trim((string) $timeValue);

        try {
            // If this already contains a full date/time, parse directly.
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $timeText)) {
                return Carbon::parse($timeText);
            }

            $dateText = $dateValue ? Carbon::parse($dateValue)->toDateString() : now()->toDateString();
            return Carbon::parse($dateText . ' ' . $timeText);
        } catch (\Exception $e) {
            Log::warning('Failed to parse attendance date/time', [
                'date_value' => $dateValue,
                'time_value' => $timeValue,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function queueAttendanceNotification(Student $student, Attendance $attendance, string $attendanceType, int $teacherId): void
    {
        try {
            if (!$student->contact_person_contact) {
                Log::info('No contact number for student, skipping notification', [
                    'student_id' => $student->id
                ]);
                return;
            }

            $message = $this->generateNotificationMessage($student, $attendanceType, $attendance);

            OutboundMessage::create([
                'teacher_id' => $teacherId,
                'student_id' => $student->id,
                'contact_number' => $student->contact_person_contact,
                'message' => $message,
                'status' => 'pending',
                'recipient_type' => 'individual',
                'recipient_count' => 1
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue attendance notification', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function generateNotificationMessage(Student $student, string $attendanceType, Attendance $attendance): string
    {
        $timeField = match($attendanceType) {
            'time_in_am' => $attendance->time_in_am,
            'time_out_am' => $attendance->time_out_am,
            'time_in_pm' => $attendance->time_in_pm,
            'time_out_pm' => $attendance->time_out_pm,
            default => now()
        };

        $time = Carbon::parse($timeField)->format('g:i A');
        
        $action = match($attendanceType) {
            'time_in_am' => 'Morning Time In',
            'time_out_am' => 'Morning Time Out',
            'time_in_pm' => 'Afternoon Time In',
            'time_out_pm' => 'Afternoon Time Out',
            default => 'Attendance'
        };

        return "Your child {$student->name} - {$action} recorded at {$time}";
    }

    private function validateAttendanceTimeWindow(Section $section): array
    {
        $now = Carbon::now('Asia/Manila');
        $currentTime = $now->format('H:i:s');
        
         $validWindows = [];
        
        if ($section->am_time_in_start && $section->am_time_in_end) {
            $amInStart = Carbon::parse($section->am_time_in_start)->format('H:i:s');
            $amInEnd = Carbon::parse($section->am_time_in_end)->format('H:i:s');
            if ($currentTime >= $amInStart && $currentTime <= $amInEnd) {
                return ['valid' => true];
            }
            $validWindows[] = 'AM In: ' . Carbon::parse($section->am_time_in_start)->format('g:i A') . ' - ' . Carbon::parse($section->am_time_in_end)->format('g:i A');
        }
        
        if ($section->am_time_out_start && $section->am_time_out_end) {
            $amOutStart = Carbon::parse($section->am_time_out_start)->format('H:i:s');
            $amOutEnd = Carbon::parse($section->am_time_out_end)->format('H:i:s');
            if ($currentTime >= $amOutStart && $currentTime <= $amOutEnd) {
                return ['valid' => true];
            }
            $validWindows[] = 'AM Out: ' . Carbon::parse($section->am_time_out_start)->format('g:i A') . ' - ' . Carbon::parse($section->am_time_out_end)->format('g:i A');
        }
        
        if ($section->pm_time_in_start && $section->pm_time_in_end) {
            $pmInStart = Carbon::parse($section->pm_time_in_start)->format('H:i:s');
            $pmInEnd = Carbon::parse($section->pm_time_in_end)->format('H:i:s');
            if ($currentTime >= $pmInStart && $currentTime <= $pmInEnd) {
                return ['valid' => true];
            }
            $validWindows[] = 'PM In: ' . Carbon::parse($section->pm_time_in_start)->format('g:i A') . ' - ' . Carbon::parse($section->pm_time_in_end)->format('g:i A');
        }
        
        if ($section->pm_time_out_start && $section->pm_time_out_end) {
            $pmOutStart = Carbon::parse($section->pm_time_out_start)->format('H:i:s');
            $pmOutEnd = Carbon::parse($section->pm_time_out_end)->format('H:i:s');
            if ($currentTime >= $pmOutStart && $currentTime <= $pmOutEnd) {
                return ['valid' => true];
            }
            $validWindows[] = 'PM Out: ' . Carbon::parse($section->pm_time_out_start)->format('g:i A') . ' - ' . Carbon::parse($section->pm_time_out_end)->format('g:i A');
        }
        
         if (empty($validWindows)) {
            return ['valid' => true];
        }
        
        // Current time is outside all configured windows
        $windowsList = implode(', ', $validWindows);
        return [
            'valid' => false,
            'error' => 'Current time (' . $now->format('g:i A') . ') is outside configured attendance hours. Valid windows: ' . $windowsList
        ];
    }
}
