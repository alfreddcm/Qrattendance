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

        // Log the incoming QR data for debugging
        Log::info('QR scan attempt', [
            'qr_data' => $qrData,
            'qr_data_length' => strlen($qrData),
            'qr_data_type' => gettype($qrData),
            'scanner_type' => $scannerType,
            'user_id' => Auth::id(),
        ]);

        // Validate and find student by stud_code
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

        // Find student by stud_code with additional verification
        $student = Student::with('user')
                         ->where('user_id', Auth::id())
                         ->where('stud_code', $qrData)
                         ->whereNotNull('stud_code')
                         ->where('stud_code', '!=', '')
                         ->first();

        // Log search results
        Log::info('Student search results', [
            'scanner_type' => $scannerType,
            'stud_code' => $qrData,
            'student_found' => $student ? true : false,
            'user_id' => Auth::id(),
        ]);

        if (!$student) {
            // Try to find any student with this stud_code (for debugging)
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
        $time = $now->format('H:i:s');  
        
        $periods = [
            'AM Time In' => ['start' => $semester->am_time_in_start,  'end' => $semester->am_time_in_end,  'field' => 'time_in_am',  'requires' => null],
            'AM Time Out'=> ['start' => $semester->am_time_out_start,'end' => $semester->am_time_out_end,'field' => 'time_out_am','requires' => null],
            'PM Time In' => ['start' => $semester->pm_time_in_start,  'end' => $semester->pm_time_in_end,  'field' => 'time_in_pm',  'requires' => null],
            'PM Time Out'=> ['start' => $semester->pm_time_out_start,'end' => $semester->pm_time_out_end,'field' => 'time_out_pm','requires' => null],
        ];

        foreach ($periods as $label => $details) {
            if (!$details['start'] || !$details['end']) continue;

            $start = Carbon::createFromFormat('H:i:s', $details['start']);
            $end = Carbon::createFromFormat('H:i:s', $details['end']);

            if ($now->between($start, $end)) {
                if ($attendance->{$details['field']}) {
                    // Get the recorded time and format it safely
                    $existingTime = $attendance->{$details['field']};
                    if ($existingTime) {
                        try {
                            $recordedTime = Carbon::createFromFormat('H:i:s', $existingTime)->format('g:i:s A');
                        } catch (Exception $e) {
                            // Fallback if format fails
                            $recordedTime = $existingTime;
                        }
                    } else {
                        $recordedTime = 'Unknown time';
                    }
                    
                    Log::info('Duplicate scan', [
                        'scanner_type' => $scannerType,
                        'student_name' => $student->name,
                        'student_id_no' => $student->id_no,
                        'period' => $label,
                        'already_recorded_time' => $recordedTime,
                    ]);
                    
                    return response()->json([
                        'success' => true, 
                        'attendance_recorded' => false,
                        'message' => "$label already recorded at " . $recordedTime, 
                        'period' => $label,
                        'student' => [
                            'id_no' => $student->id_no,
                            'name' => $student->name,
                            'picture' => $student->picture,
                            'section' => $student->user ? $student->user->section_name : 'No Section',
                            'semester' => $semester->name ?? "Semester {$student->semester_id}",
                        ],
                        'status' => "$label already recorded!",
                        'time_period' => $label,
                        'recorded_time' => $recordedTime,
                    ]); 
                }

                $attendance->{$details['field']} = $time;
                $attendance->save();

                // Record session activity if there's an active session
                $activeSession = AttendanceSession::where('teacher_id', Auth::id())
                                                 ->active()
                                                 ->first();
                if ($activeSession) {
                    $activeSession->recordAttendance();
                }


                // Send SMS notification to parent/guardian ONLY if newly recorded
                $this->sendAttendanceNotification($student, $label, $now->format('g:i A'));

                Log::info('Attendance recorded', [
                    'scanner_type' => $scannerType,
                    'student_name' => $student->name,
                    'student_id_no' => $student->id_no,
                    'period' => $label,
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
                        'section' => $student->user ? $student->user->section_name : 'No Section',
                        'semester' => $semester->name ?? "Semester {$student->semester_id}",
                    ],
                    'status' => "$label recorded successfully!",
                    'time_period' => $label,
                    'recorded_time' => $now->format('g:i:s A'), // Display in 12-hour format
                ]);
            }
        }

        Log::warning('Scan outside time period', [
            'scanner_type' => $scannerType,
            'student_name' => $student->name,
            'current_time' => $now->format('g:i:s A'),
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => false, 
            'message' => 'Attendance not allowed at this time.',
            'student' => [
                'id_no' => $student->id_no,
                'name' => $student->name,
                'picture' => $student->picture,
                'section' => $student->user ? $student->user->section_name : 'No Section',
                'semester' => $semester->name ?? "Semester {$student->semester_id}",
            ],
            'status' => 'Outside recording hours',
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

    /**
     * Verify student information and data integrity
     */
    private function verifyStudentInfo(Student $student)
    {
        // Check if student has required fields
        if (empty($student->name) || empty($student->id_no)) {
            Log::warning('Student missing required information', [
                'student_id' => $student->id,
                'has_name' => !empty($student->name),
                'has_id_no' => !empty($student->id_no),
                'user_id' => Auth::id(),
            ]);
            return false;
        }

        // Check if student has valid stud_code
        if (empty($student->stud_code)) {
            Log::warning('Student missing stud_code', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'user_id' => Auth::id(),
            ]);
            return false;
        }

        // Verify stud_code format (should be id_no + underscore + 10 characters)
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

        // Check if stud_code has correct length (id_no + _ + 10 random chars)
        $expectedLength = strlen($student->id_no) + 1 + 10; // id_no + underscore + 10 chars
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

        // Check if student belongs to current teacher
        if ($student->user_id !== Auth::id()) {
            Log::warning('Student does not belong to current teacher', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_teacher_id' => $student->user_id,
                'current_teacher_id' => Auth::id(),
            ]);
            return false;
        }

        // Check if student has valid semester
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

    /**
     * Send SMS notification for attendance recording
     */
    private function sendAttendanceNotification($student, $attendanceStatus, $recordedTime)
    {
        try {
            // Check if student has a valid contact number
            if (!$student->contact_person_contact) {
                Log::info('No contact number for student, skipping SMS', [
                    'student_id' => $student->id,
                    'student_name' => $student->name
                ]);
                return;
            }

            // Generate the attendance message
            $message = $this->generateAttendanceMessage($student, $attendanceStatus, $recordedTime);

            // Send SMS using the service
            $smsService = new AndroidSmsGatewayService();
            $result = $smsService->sendSms($message, $student->contact_person_contact);

            // Record the SMS in OutboundMessage table
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

    /**
     * Generate attendance message for SMS
     */
    private function generateAttendanceMessage($student, $status, $time)
    {
        // Determine if it's Time In or Time Out based on status
        if (stripos($status, 'IN') !== false) {
            $attendanceType = 'Time In';
        } elseif (stripos($status, 'OUT') !== false) {
            $attendanceType = 'Time Out';
        } else {
            $attendanceType = $status; // fallback
        }
        
        $timeFormatted = \Carbon\Carbon::parse($time)->format('g:i A');
        
        return "Your child {$student->name} {$attendanceType} attendance recorded at {$timeFormatted}";
    }
}
