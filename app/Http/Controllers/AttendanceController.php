<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Semester;
use App\Models\AttendanceSession;
use App\Models\OutboundMessage;
use App\Services\AndroidSmsGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        $semester = Semester::find($student->semester_id);
        if (!$semester || $semester->status !== 'active') {
            Log::warning('No active semester', [
                'scanner_type' => $scannerType,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'semester_id' => $student->semester_id,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => 'No active semester.']);
        }

         $attendance = Attendance::firstOrCreate([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'date' => Carbon::now()->toDateString(),
        ], [
            'school_id' => Auth::user()->school_id,
            'teacher_id' => Auth::id(),
        ]);

        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        
         $section = $student->section;
        $timeSource = $section ?: $semester;
        
         $periods = [
            'am_time_in' => [
                'start' => $timeSource->am_time_in_start ?? $semester->am_time_in_start,
                'end' => $timeSource->am_time_in_end ?? $semester->am_time_in_end,
                'field' => 'time_in_am',
                'status_field' => 'am_status',
                'label' => 'AM Time In'
            ],
            'am_time_out' => [
                'start' => $timeSource->am_time_out_start ?? $semester->am_time_out_start,
                'end' => $timeSource->am_time_out_end ?? $semester->am_time_out_end,
                'field' => 'time_out_am',
                'status_field' => 'am_status',
                'label' => 'AM Time Out'
            ],
            'pm_time_in' => [
                'start' => $timeSource->pm_time_in_start ?? $semester->pm_time_in_start,
                'end' => $timeSource->pm_time_in_end ?? $semester->pm_time_in_end,
                'field' => 'time_in_pm',
                'status_field' => 'pm_status',
                'label' => 'PM Time In'
            ],
            'pm_time_out' => [
                'start' => $timeSource->pm_time_out_start ?? $semester->pm_time_out_start,
                'end' => $timeSource->pm_time_out_end ?? $semester->pm_time_out_end,
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
                    'semester' => $semester->name ?? "Semester {$student->semester_id}",
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
                'semester' => $semester->name ?? "Semester {$student->semester_id}",
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

         if (empty($student->semester_id)) {
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

 
    private function timeToMinutes($time)
    {
        $parts = explode(':', $time);
        return ($parts[0] * 60) + $parts[1];
    }
}
