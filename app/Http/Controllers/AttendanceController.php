<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\AttendanceSession;
use App\Models\OutboundMessage;
use App\Services\AndroidSmsGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class AttendanceController extends Controller
{
    public function verifyQrAndRecordAttendance(Request $request)
    {
         Log::info('QR scan request received', [
            'qr_data' => $request->input('qr_data'),
            'scanner_type' => $request->input('scanner_type'),
            'user_id' => Auth::id(),
        ]);

        $request->validate(['qr_data' => 'required|string']);
        $qrData = $request->qr_data;
        $scannerType = $this->detectScannerType($request);

         Log::info('QR scan attempt', [
            'qr_data' => $qrData,
            'qr_data_length' => strlen($qrData),
            'qr_data_type' => gettype($qrData),
            'scanner_type' => $scannerType,
            'user_id' => Auth::id(),
        ]);

         if (empty($qrData) || strlen($qrData) < 3) {
            Log::warning('Invalid QR data format', [
                'scanner_type' => $scannerType,
                'qr_data' => $qrData,
                'qr_data_length' => strlen($qrData),
                'qr_data_empty' => empty($qrData),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid QR code format.']);
        }

         $student = Student::with('user')
                         ->where('user_id', Auth::id())
                         ->where('stud_code', $qrData)
                         ->whereNotNull('stud_code')
                         ->where('stud_code', '!=', '')
                         ->first();

         Log::info('Student search results', [
            'scanner_type' => $scannerType,
            'stud_code' => $qrData,
            'student_found' => $student ? true : false,
            'user_id' => Auth::id(),
        ]);

        if (!$student) {
             $anyStudent = Student::where('stud_code', $qrData)->first();
            
            Log::warning('Student not found with stud_code', [
                'scanner_type' => $scannerType,
                'stud_code' => $qrData,
                'any_student_found' => $anyStudent ? true : false,
                'any_student_teacher_id' => $anyStudent ? $anyStudent->user_id : null,
                'current_teacher_id' => Auth::id(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => 'Student not found. Please check your QR code or contact your teacher.']);
        }

         if (!$this->verifyStudentInfo($student)) {
            Log::warning('Student verification failed', [
                'scanner_type' => $scannerType,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'stud_code' => $qrData,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => 'Student verification failed. Please contact your teacher.']);
        }

        $schoolYear = SchoolYear::find($student->school_year_id);
        if (!$schoolYear || $schoolYear->status !== 'active') {
            Log::warning('No active semester', [
                'scanner_type' => $scannerType,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'school_year_id' => $student->school_year_id,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => 'No active semester.']);
        }

         $attendance = Attendance::firstOrCreate([
            'student_id' => $student->id,
            'school_year_id' => $schoolYear->id,
            'date' => Carbon::now()->toDateString(),
        ], [
            'school_id' => Auth::user()->school_id,
            'teacher_id' => Auth::id(),
        ]);

        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        
         $section = $student->section;
        
         if (!$section) {
            Log::warning('Student has no section assigned', [
                'scanner_type' => $scannerType,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Student is not assigned to any section. Please contact your teacher.'
            ]);
        }
        
         $periods = [
            'am_time_in' => [
                'start' => $section->am_time_in_start,
                'end' => $section->am_time_in_end,
                'field' => 'time_in_am',
                'status_field' => 'am_status',
                'label' => 'AM Time In'
            ],
            'am_time_out' => [
                'start' => $section->am_time_out_start,
                'end' => $section->am_time_out_end,
                'field' => 'time_out_am',
                'status_field' => 'am_status',
                'label' => 'AM Time Out'
            ],
            'pm_time_in' => [
                'start' => $section->pm_time_in_start,
                'end' => $section->pm_time_in_end,
                'field' => 'time_in_pm',
                'status_field' => 'pm_status',
                'label' => 'PM Time In'
            ],
            'pm_time_out' => [
                'start' => $section->pm_time_out_start,
                'end' => $section->pm_time_out_end,
                'field' => 'time_out_pm',
                'status_field' => 'pm_status',
                'label' => 'PM Time Out'
            ],
        ];

         $recordedPeriod = $this->determineAndRecordAttendance($attendance, $periods, $currentTime, $now);
        
        if ($recordedPeriod) {
             $this->updateAttendanceStatus($attendance);
            
             $activeSession = AttendanceSession::where('teacher_id', Auth::id())
                                             ->where('status', 'active')
                                             ->whereDate('started_at', Carbon::today('Asia/Manila'))
                                             ->first();
            if ($activeSession) {
                $activeSession->recordAttendance();
            }

             $this->sendAttendanceNotification($student, $recordedPeriod['label'], $now->format('g:i A'));

            Log::info('Attendance recorded with new system', [
                'scanner_type' => $scannerType,
                'student_name' => $student->name,
                'student_id_no' => $student->id_no,
                'period' => $recordedPeriod['label'],
                'status' => $recordedPeriod['status'],
                'recorded_time' => $now->format('g:i:s A'),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'attendance_recorded' => true,
                'student' => [
                    'id_no' => $student->id_no,
                    'name' => $student->name,
                    'picture' => $student->picture,
                    'section' => $student->section ? $student->section->name : 'No Section',
                    'semester' => $schoolYear->name ?? "Semester {$student->school_year_id}",
                ],
                'status' => $recordedPeriod['message'],
                'time_period' => $recordedPeriod['label'],
                'attendance_status' => $recordedPeriod['status'],
                'recorded_time' => $now->format('g:i:s A'),
                'remarks' => $attendance->remarks,
            ]);
        }

         Log::warning('No period matched - should not happen with flexible system', [
            'scanner_type' => $scannerType,
            'student_name' => $student->name,
            'current_time' => $now->format('g:i:s A'),
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => false, 
            'message' => 'Unable to determine attendance period. Please contact your teacher.',
            'student' => [
                'id_no' => $student->id_no,
                'name' => $student->name,
                'picture' => $student->picture,
                'section' => $student->section ? $student->section->name : 'No Section',
                'semester' => $schoolYear->name ?? "Semester {$student->school_year_id}",
            ],
            'status' => 'Period determination failed',
            'current_time' => $now->format('g:i:s A'),
        ]);
    }
   
    private function detectScannerType(Request $request)
    {
        $userAgent = $request->header('User-Agent', '');
        $referer = $request->header('Referer', '');
        
         if ($request->has('scanner_type')) {
            return $request->input('scanner_type');
        }
        
         if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'iPhone') !== false) {
            return 'Mobile Camera';
        }
        
         return 'Webcam/USB Scanner';
    }
 
    private function verifyStudentInfo(Student $student)
    {
         if (empty($student->name) || empty($student->id_no)) {
            Log::warning('Student missing required information', [
                'student_id' => $student->id,
                'has_name' => !empty($student->name),
                'has_id_no' => !empty($student->id_no),
                'user_id' => Auth::id(),
            ]);
            return false;
        }

         if (empty($student->stud_code)) {
            Log::warning('Student missing stud_code', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'user_id' => Auth::id(),
            ]);
            return false;
        }

         $expectedPrefix = $student->id_no . '_';
        if (substr($student->stud_code, 0, strlen($expectedPrefix)) !== $expectedPrefix) {
            Log::warning('Student stud_code format invalid', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'stud_code' => $student->stud_code,
                'expected_prefix' => $expectedPrefix,
                'user_id' => Auth::id(),
            ]);
            return false;
        }

         $expectedLength = strlen($student->id_no) + 1 + 10;  
        if (strlen($student->stud_code) !== $expectedLength) {
            Log::warning('Student stud_code length invalid', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'stud_code' => $student->stud_code,
                'stud_code_length' => strlen($student->stud_code),
                'expected_length' => $expectedLength,
                'user_id' => Auth::id(),
            ]);
            return false;
        }

         if ($student->user_id !== Auth::id()) {
            Log::warning('Student does not belong to current teacher', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_teacher_id' => $student->user_id,
                'current_teacher_id' => Auth::id(),
            ]);
            return false;
        }

         if (empty($student->school_year_id)) {
            Log::warning('Student missing semester assignment', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'user_id' => Auth::id(),
            ]);
            return false;
        }

        return true;
    }

 
    private function sendAttendanceNotification($student, $attendanceStatus, $recordedTime)
    {
        try {
             if (!$student->contact_person_contact) {
                Log::info('No contact number for student, skipping SMS', [
                    'student_id' => $student->id,
                    'student_name' => $student->name
                ]);
                return;
            }

             $message = $this->generateAttendanceMessage($student, $attendanceStatus, $recordedTime);

             $smsService = new AndroidSmsGatewayService();
            $result = $smsService->sendSms($message, $student->contact_person_contact);

             $outboundMessage = OutboundMessage::create([
                'teacher_id' => Auth::id(),
                'student_id' => $student->id,
                'contact_number' => $student->contact_person_contact,
                'message' => $message,
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['success'] ? 'sent' : 'failed',
                'recipient_type' => 'individual',
                'recipient_count' => 1
            ]);

            if ($result['success']) {
                Log::info('SMS notification sent for attendance', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'contact_number' => $student->contact_person_contact,
                    'attendance_status' => $attendanceStatus,
                    'outbound_message_id' => $outboundMessage->id
                ]);
            } else {
                Log::warning('SMS notification failed', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'contact_number' => $student->contact_person_contact,
                    'error' => $result['error'] ?? 'Unknown error',
                    'outbound_message_id' => $outboundMessage->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

 
    private function generateAttendanceMessage($student, $status, $time)
    {
         if (stripos($status, 'IN') !== false) {
            $attendanceType = 'Time In';
        } elseif (stripos($status, 'OUT') !== false) {
            $attendanceType = 'Time Out';
        } else {
            $attendanceType = $status;  
        }
        
        $timeFormatted = \Carbon\Carbon::parse($time)->format('g:i A');
        
        return "Your child {$student->name} {$attendanceType} attendance recorded at {$timeFormatted}";
    }

 
    private function determineAndRecordAttendance($attendance, $periods, $currentTime, $now)
    {
         foreach ($periods as $periodKey => $period) {
            if (!$period['start'] || !$period['end']) continue;
            
            $start = Carbon::createFromFormat('H:i:s', $period['start']);
            $end = Carbon::createFromFormat('H:i:s', $period['end']);
            
            if ($now->between($start, $end)) {
                return $this->recordPeriodAttendance($attendance, $period, $currentTime, 'On Time', $now);
            }
        }
        
         $bestMatch = $this->findClosestPeriod($periods, $currentTime);
        
        if ($bestMatch) {
            $status = $this->determineAttendanceStatus($bestMatch, $currentTime);
            return $this->recordPeriodAttendance($attendance, $bestMatch, $currentTime, $status, $now);
        }
        
        return null;
    }

  
    private function findClosestPeriod($periods, $currentTime)
    {
        $currentMinutes = $this->timeToMinutes($currentTime);
        $closestPeriod = null;
        $smallestDifference = PHP_INT_MAX;
        
        foreach ($periods as $periodKey => $period) {
            if (!$period['start'] || !$period['end']) continue;
            
            $startMinutes = $this->timeToMinutes($period['start']);
            $endMinutes = $this->timeToMinutes($period['end']);
            $periodMiddle = ($startMinutes + $endMinutes) / 2;
            
            $difference = abs($currentMinutes - $periodMiddle);
            
            if ($difference < $smallestDifference) {
                $smallestDifference = $difference;
                $closestPeriod = $period;
            }
        }
        
        return $closestPeriod;
    }

 
    private function determineAttendanceStatus($period, $currentTime)
    {
        $currentMinutes = $this->timeToMinutes($currentTime);
        $startMinutes = $this->timeToMinutes($period['start']);
        $endMinutes = $this->timeToMinutes($period['end']);
        
         if ($currentMinutes < $startMinutes - 30) {
            return 'Early';
        }
        
         if ($currentMinutes >= $startMinutes - 15 && $currentMinutes <= $endMinutes + 15) {
            return 'On Time';
        }
        
         if ($currentMinutes > $endMinutes + 15 && $currentMinutes <= $endMinutes + 60) {
            return 'Tardy';
        }
        
         return 'Late';
    }
 
    private function recordPeriodAttendance($attendance, $period, $currentTime, $status, $now)
    {
         if ($attendance->{$period['field']}) {
            $existingTime = $attendance->{$period['field']};
            try {
                $recordedTime = Carbon::createFromFormat('H:i:s', $existingTime)->format('g:i:s A');
            } catch (Exception $e) {
                $recordedTime = $existingTime;
            }
            
            return [
                'label' => $period['label'],
                'status' => $status,
                'message' => "{$period['label']} already recorded at {$recordedTime}",
                'already_recorded' => true,
                'recorded_time' => $recordedTime
            ];
        }
        
         $attendance->{$period['field']} = $currentTime;
        $attendance->{$period['status_field']} = $status;
        $attendance->save();
        
        return [
            'label' => $period['label'],
            'status' => $status,
            'message' => "{$period['label']} recorded successfully! Status: {$status}",
            'already_recorded' => false,
            'recorded_time' => $now->format('g:i:s A')
        ];
    }

    
    private function updateAttendanceStatus($attendance)
    {
        $hasAM = $attendance->time_in_am || $attendance->time_out_am;
        $hasPM = $attendance->time_in_pm || $attendance->time_out_pm;
        
         if ($hasAM && $hasPM) {
            $attendance->remarks = 'Present';
        } elseif ($hasAM || $hasPM) {
            $attendance->remarks = 'Partial';
        } else {
            $attendance->remarks = 'Absent';
        }
        
        $attendance->save();
    }

    /**
     * Get student's attendance records for today with period interpretation
     */
    public function getStudentAttendanceToday($studentId)
    {
        try {
            $student = Student::find($studentId);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found.']);
            }

            $schoolYear = SchoolYear::find($student->school_year_id);
            if (!$schoolYear) {
                return response()->json(['success' => false, 'message' => 'No semester found.']);
            }

            // Get today's attendance records for this student
            $todayAttendance = Attendance::where('student_id', $studentId)
                ->whereDate('created_at', Carbon::today('Asia/Manila'))
                ->get();

            // Define time periods
            $timeSchedules = [];
            if ($schoolYear->am_time_in_start && $schoolYear->am_time_in_end) {
                $timeSchedules['am_time_in'] = [
                    'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->am_time_in_start),
                    'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->am_time_in_end)
                ];
            }
            if ($schoolYear->am_time_out_start && $schoolYear->am_time_out_end) {
                $timeSchedules['am_time_out'] = [
                    'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->am_time_out_start),
                    'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->am_time_out_end)
                ];
            }
            if ($schoolYear->pm_time_in_start && $schoolYear->pm_time_in_end) {
                $timeSchedules['pm_time_in'] = [
                    'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->pm_time_in_start),
                    'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->pm_time_in_end)
                ];
            }
            if ($schoolYear->pm_time_out_start && $schoolYear->pm_time_out_end) {
                $timeSchedules['pm_time_out'] = [
                    'start_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->pm_time_out_start),
                    'end_time' => Carbon::today('Asia/Manila')->setTimeFromTimeString($schoolYear->pm_time_out_end)
                ];
            }

            // Check which periods have records
            $records = [
                'am-in' => false,
                'am-out' => false,
                'pm-in' => false,
                'pm-out' => false
            ];

            foreach ($todayAttendance as $attendance) {
                $recordTime = $attendance->time_in ?: $attendance->time_out;
                if (!$recordTime) continue;

                foreach ($timeSchedules as $periodType => $schedule) {
                    if ($recordTime->between($schedule['start_time'], $schedule['end_time'])) {
                        switch ($periodType) {
                            case 'am_time_in':
                                $records['am-in'] = true;
                                break;
                            case 'am_time_out':
                                $records['am-out'] = true;
                                break;
                            case 'pm_time_in':
                                $records['pm-in'] = true;
                                break;
                            case 'pm_time_out':
                                $records['pm-out'] = true;
                                break;
                        }
                        break;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'records' => $records
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching student attendance today', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Error fetching attendance records.']);
        }
    }

    public function teacherRecordAttendance(Request $request)
    {
        try {
            $teacher = Auth::user();
            if (!$teacher || $teacher->role !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Teacher access required.'
                ], 403);
            }

            $request->validate([
                'student_id' => 'required|exists:students,id',
                'section_id' => 'required|exists:sections,id',
                'attendance_type' => 'required|in:time_in_am,time_out_am,time_in_pm,time_out_pm',
                'override_time' => 'nullable|date_format:H:i',
                'status' => 'nullable|in:Early,On Time,Tardy,Late',
                'remarks' => 'nullable|string|max:255',
            ]);

            $student = Student::with('section', 'semester')->findOrFail($request->student_id);

            if ($student->section_id != $request->section_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student does not belong to this section'
                ], 403);
            }

            $section = Section::find($request->section_id);
            if ($section->teacher_id != $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this section'
                ], 403);
            }

            // Get or create active session
            $session = AttendanceSession::where('teacher_id', $teacher->id)
                ->where('section_id', $section->id)
                ->where('status', 'active')
                ->whereDate('started_at', today())
                ->first();

            $recordTime = $request->override_time 
                ? Carbon::createFromFormat('H:i', $request->override_time)
                : now();

            $status = $request->status ?? $this->calculateAttendanceStatus(
                $section,
                $request->attendance_type,
                $recordTime
            );

            $columnMap = [
                'time_in_am' => ['column' => 'time_in_am', 'status_column' => 'am_status'],
                'time_out_am' => ['column' => 'time_out_am', 'status_column' => 'am_status'],
                'time_in_pm' => ['column' => 'time_in_pm', 'status_column' => 'pm_status'],
                'time_out_pm' => ['column' => 'time_out_pm', 'status_column' => 'pm_status'],
            ];

            $mapping = $columnMap[$request->attendance_type];

            $attendance = $this->saveAttendanceRecord(
                $student->id,
                $student->school_year_id,
                $section->id,
                $session ? $session->id : null,
                $mapping['column'],
                $mapping['status_column'],
                $status,
                $teacher->id,
                $request->remarks ? "Manual entry | {$request->remarks}" : 'Manually recorded by teacher',
                $recordTime
            );

            $this->queueAttendanceNotification(
                $student,
                $attendance,
                $request->attendance_type,
                $teacher->id
            );

            Log::info('Teacher manually recorded attendance', [
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'attendance_type' => $request->attendance_type,
                'status' => $status
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->getAttendanceSuccessMessage($request->attendance_type, $status),
                'attendance' => $attendance,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'id_no' => $student->id_no,
                ],
                'recorded_time' => $recordTime->format('h:i A'),
                'status' => $status,
            ]);

        } catch (Exception $e) {
            Log::error('Error in teacher manual attendance recording', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function teacherRecordBulkAttendance(Request $request)
    {
        try {
            $teacher = Auth::user();
            if (!$teacher || $teacher->role !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Teacher access required.'
                ], 403);
            }

            $request->validate([
                'section_id' => 'required|exists:sections,id',
                'attendance_type' => 'required|in:time_in_am,time_out_am,time_in_pm,time_out_pm',
                'students' => 'required|array',
                'students.*.student_id' => 'required|exists:students,id',
                'students.*.status' => 'nullable|in:Early,On Time,Tardy,Late',
                'students.*.time' => 'nullable|date_format:H:i',
                'students.*.remarks' => 'nullable|string|max:255',
            ]);

            $section = Section::findOrFail($request->section_id);
            if ($section->teacher_id != $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this section'
                ], 403);
            }

            // Get or create active session
            $session = AttendanceSession::where('teacher_id', $teacher->id)
                ->where('section_id', $section->id)
                ->where('status', 'active')
                ->whereDate('started_at', today())
                ->first();

            $schoolYear = SchoolYear::findOrFail($section->school_year_id);

            $result = $this->processBulkAttendanceRecord(
                $section->id,
                $schoolYear->id,
                $session ? $session->id : null,
                $request->attendance_type,
                $request->students,
                $teacher->id
            );

            Log::info('Teacher bulk recorded attendance', [
                'teacher_id' => $teacher->id,
                'section_id' => $section->id,
                'attendance_type' => $request->attendance_type,
                'total' => $result['total'],
                'recorded' => $result['recorded'],
                'failed' => $result['failed']
            ]);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Error in teacher bulk attendance recording', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record bulk attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance report for a section
     * Route: GET /api/teacher/attendance/report
     */
    public function getAttendanceReport(Request $request)
    {
        try {
            $teacher = Auth::user();
            if (!$teacher || $teacher->role !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Teacher access required.'
                ], 403);
            }

            $request->validate([
                'section_id' => 'required|exists:sections,id',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
            ]);

            $section = Section::findOrFail($request->section_id);
            if ($section->teacher_id != $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this section'
                ], 403);
            }

            $attendances = Attendance::with(['student'])
                ->where('section_id', $request->section_id)
                ->whereBetween('date', [$request->date_from, $request->date_to])
                ->orderBy('date')
                ->orderBy('student_id')
                ->get();

            $students = Student::where('section_id', $request->section_id)->get();

            $report = [
                'section' => [
                    'id' => $section->id,
                    'name' => $section->name,
                    'gradelevel' => $section->gradelevel,
                ],
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ],
                'total_students' => $students->count(),
                'summary' => [
                    'total_records' => $attendances->count(),
                    'on_time' => $attendances->where('am_status', 'On Time')->count() + $attendances->where('pm_status', 'On Time')->count(),
                    'late' => $attendances->where('am_status', 'Late')->count() + $attendances->where('pm_status', 'Late')->count(),
                    'tardy' => $attendances->where('am_status', 'Tardy')->count() + $attendances->where('pm_status', 'Tardy')->count(),
                ],
                'students' => [],
            ];

            foreach ($students as $student) {
                $studentAttendances = $attendances->where('student_id', $student->id);
                
                $report['students'][] = [
                    'student_id' => $student->id,
                    'id_no' => $student->id_no,
                    'name' => $student->name,
                    'total_days' => $studentAttendances->unique('date')->count(),
                    'records' => $studentAttendances->values(),
                ];
            }

            return response()->json([
                'success' => true,
                'report' => $report
            ]);

        } catch (Exception $e) {
            Log::error('Error generating attendance report', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }
 
    private function timeToMinutes($time)
    {
        $parts = explode(':', $time);
        return ($parts[0] * 60) + $parts[1];
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

        // SCENARIO 1: No attendance record yet today
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
        $recordTime = $overrideTime ?? now();

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

    private function validateAttendanceTimeWindow(): array
    {
        $now = now();
        $currentHour = $now->hour;
        
        if ($currentHour < 6 || $currentHour >= 18) {
            return [
                'valid' => false,
                'error' => 'Outside attendance hours (6:00 AM - 6:00 PM)'
            ];
        }

        return ['valid' => true];
    }

    private function processBulkAttendanceRecord(
        int $sectionId,
        int $schoolYearId,
        ?int $sessionId,
        string $attendanceType,
        array $students,
        int $teacherId
    ): array {
        $results = [
            'success' => true,
            'total' => count($students),
            'recorded' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($sectionId, $schoolYearId, $sessionId, $attendanceType, $students, $teacherId, &$results) {
            foreach ($students as $studentData) {
                try {
                    $studentId = $studentData['student_id'];
                    $status = $studentData['status'] ?? 'On Time';
                    $remarks = $studentData['remarks'] ?? null;
                    $overrideTime = isset($studentData['time']) ? Carbon::parse($studentData['time']) : null;

                    $columnMap = [
                        'time_in_am' => ['column' => 'time_in_am', 'status_column' => 'am_status'],
                        'time_out_am' => ['column' => 'time_out_am', 'status_column' => 'am_status'],
                        'time_in_pm' => ['column' => 'time_in_pm', 'status_column' => 'pm_status'],
                        'time_out_pm' => ['column' => 'time_out_pm', 'status_column' => 'pm_status'],
                    ];

                    if (!isset($columnMap[$attendanceType])) {
                        throw new \Exception('Invalid attendance type');
                    }

                    $column = $columnMap[$attendanceType]['column'];
                    $statusColumn = $columnMap[$attendanceType]['status_column'];

                    $this->saveAttendanceRecord(
                        $studentId,
                        $schoolYearId,
                        $sectionId,
                        $sessionId,
                        $column,
                        $statusColumn,
                        $status,
                        $teacherId,
                        $remarks ? "Bulk recorded | {$remarks}" : 'Bulk recorded by teacher',
                        $overrideTime
                    );

                    $results['recorded']++;

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'student_id' => $studentData['student_id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    $results['failed']++;
                }
            }

            if ($results['failed'] > 0) {
                $results['success'] = false;
            }
        });

        return $results;
    }
}
