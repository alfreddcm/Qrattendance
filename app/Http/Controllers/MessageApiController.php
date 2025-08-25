<?php

namespace App\Http\Controllers;

use App\Models\OutboundMessage;
use App\Models\Student;
use App\Models\User;
use App\Services\AndroidSmsGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class MessageApiController extends Controller
{
    protected $smsService;
    protected $teacherId;

    public function __construct(AndroidSmsGatewayService $smsService)
    {
        $this->smsService = $smsService;
        $this->teacherId = null;
    }

    /**
     * Set teacher ID for OutboundMessage creation
     */
    public function setTeacherId($teacherId)
    {
        $this->teacherId = $teacherId;
    }

    /**
     * Send SMS message via Android SMS Gateway
     */
    public function sendSms(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'number' => 'required|string',
                'message' => 'required|string|max:1000',
                'student_id' => 'nullable|exists:students,id',
                'teacher_id' => 'nullable|exists:users,id',
                'send_to_all' => 'boolean',
                'send_to_all_teachers' => 'boolean',
                'send_to_teacher' => 'boolean',
                'recipient_type' => 'nullable|string|in:all_teachers,specific_teacher,all_parents,specific_student,custom'
            ]);

            if ($validator->fails()) {
                Log::warning('SMS API validation failed', [
                    'errors' => $validator->errors(),
                    'request_data' => $request->all()
                ]);
                
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Validation failed: ' . $validator->errors()->first()
                ], 422);
            }

            $number = $request->input('number');
            $message = $request->input('message');
            $studentId = $request->input('student_id');
            $teacherId = $request->input('teacher_id');
            $sendToAll = $request->input('send_to_all', false);
            $sendToAllTeachers = $request->input('send_to_all_teachers', false);
            $sendToTeacher = $request->input('send_to_teacher', false);
            $recipientType = $request->input('recipient_type');

             if ($recipientType === 'all_teachers' || $number === 'all_teachers') {
                return $this->sendToAllTeachers($message);
            }

            if ($recipientType === 'specific_teacher' && $teacherId) {
                return $this->sendToSpecificTeacher($message, $teacherId);
            }

            if ($recipientType === 'all_parents' || $number === 'all_parents') {
                return $this->sendToAllParents($message);
            }

             if ($sendToAllTeachers) {
                return $this->sendToAllTeachers($message);
            }

            if ($sendToTeacher && $teacherId) {
                return $this->sendToSpecificTeacher($message, $teacherId);
            }

            if ($sendToAll) {
                return $this->sendToAllParents($message);
            }

            if (!$this->isValidPhoneNumber($number)) {
                Log::warning('Invalid phone number format', [
                    'number' => $number,
                    'student_id' => $studentId
                ]);
                
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Invalid phone number format. Must start with +63 or 09 and have correct length.'
                ], 422);
            }

            $normalizedNumber = $this->normalizePhoneNumber($number);

            $result = $this->smsService->sendSms($message, $normalizedNumber);

            $outboundMessage = OutboundMessage::create([
                'teacher_id' => $this->teacherId ?? auth()->id(),
                'admin_id' => auth()->user()->role === 'admin' ? auth()->id() : null,
                'student_id' => $studentId,
                'contact_number' => $normalizedNumber,
                'message' => $message,
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['status'] ?? 'pending',
                'recipient_type' => 'individual',
                'recipient_count' => 1
            ]);

            if ($result['success']) {
                Log::info('SMS sent successfully', [
                    'message_id' => $result['message_id'],
                    'outbound_message_id' => $outboundMessage->id,
                    'student_id' => $studentId,
                    'number' => $normalizedNumber
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'SMS sent successfully',
                    'message_id' => $result['message_id'],
                    'outbound_message_id' => $outboundMessage->id
                ]);
            } else {
                Log::error('SMS sending failed', [
                    'message_id' => $result['message_id'] ?? null,
                    'outbound_message_id' => $outboundMessage->id,
                    'student_id' => $studentId,
                    'number' => $normalizedNumber,
                    'error' => $result['error'] ?? null
                ]);

                return response()->json([
                    'status' => 'fail',
                    'message' => 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'),
                    'message_id' => $result['message_id'] ?? null,
                    'outbound_message_id' => $outboundMessage->id
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('SMS API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'An error occurred while sending SMS'
            ], 500);
        }
    }

 
    private function sendToAllParents($message)
    {
        try {
             $students = Student::where('user_id', auth()->id())
                ->whereNotNull('contact_person_contact')
                ->where('contact_person_contact', '!=', '')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'No students with parent contact numbers found in your class'
                ], 404);
            }

            $senderId = config('sms.use_sender_id') ? config('sms.sender_id') : null;
            $successCount = 0;
            $failCount = 0;
            $recipients = [];

            foreach ($students as $student) {
                $contactNumber = $student->contact_person_contact;
                
                if (!$this->isValidPhoneNumber($contactNumber)) {
                    continue;
                }

                $normalizedNumber = $this->normalizePhoneNumber($contactNumber);
                $recipients[] = [
                    'student' => $student,
                    'number' => $normalizedNumber
                ];
            }

            $totalRecipients = count($recipients);

            if ($totalRecipients === 0) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'No valid parent contact numbers found in your class'
                ], 404);
            }

            $broadcastResult = [
                'success_count' => 0,
                'fail_count' => 0,
                'message_ids' => []
            ];

             foreach ($recipients as $recipient) {
                $student = $recipient['student'];
                $normalizedNumber = $recipient['number'];

                $result = $this->smsService->sendSms($message, $normalizedNumber);

                if ($result['success']) {
                    $successCount++;
                    if (isset($result['message_id'])) {
                        $broadcastResult['message_ids'][] = $result['message_id'];
                    }
                } else {
                    $failCount++;
                }
            }

             OutboundMessage::create([
                'teacher_id' => auth()->id(),
                'student_id' => null,  
                'contact_number' => 'broadcast',  
                'message' => $message,
                'message_id' => json_encode($broadcastResult['message_ids']),  
                'status' => $successCount > 0 ? 'sent' : 'failed',
                'recipient_type' => 'broadcast',
                'recipient_count' => $totalRecipients
            ]);

            Log::info('Broadcast SMS sent', [
                'teacher_id' => auth()->id(),
                'total_recipients' => $totalRecipients,
                'success_count' => $successCount,
                'fail_count' => $failCount
            ]);

            if ($successCount > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => "SMS sent successfully to {$successCount} parents" . 
                                ($failCount > 0 ? " ({$failCount} failed)" : ""),
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'total_count' => $totalRecipients
                ]);
            } else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Failed to send SMS to any parent',
                    'success_count' => 0,
                    'fail_count' => $failCount,
                    'total_count' => $totalRecipients
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Broadcast SMS error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'teacher_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'An error occurred while sending broadcast SMS'
            ], 500);
        }
    }

    public function getMessageStatus($id)
    {
        try {
            $outboundMessage = OutboundMessage::find($id);

            if (!$outboundMessage) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Message not found'
                ], 404);
            }

            if (!$outboundMessage->message_id) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'No message ID available for status check'
                ], 400);
            }

             $statusResult = $this->smsService->getStatus($outboundMessage->message_id);

             if ($statusResult['success']) {
                $outboundMessage->update([
                    'status' => $statusResult['status']
                ]);

                Log::info('Message status updated', [
                    'outbound_message_id' => $id,
                    'message_id' => $outboundMessage->message_id,
                    'status' => $statusResult['status']
                ]);

                return response()->json([
                    'status' => 'success',
                    'message_id' => $outboundMessage->message_id,
                    'delivery_status' => $statusResult['status'],
                    'data' => $statusResult['data'] ?? null
                ]);
            } else {
                Log::error('Failed to get message status', [
                    'outbound_message_id' => $id,
                    'message_id' => $outboundMessage->message_id,
                    'error' => $statusResult['error']
                ]);

                return response()->json([
                    'status' => 'fail',
                    'message' => 'Failed to get delivery status: ' . ($statusResult['error'] ?? 'Unknown error')
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Message status check error', [
                'outbound_message_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'An error occurred while checking message status'
            ], 500);
        }
    }

 
    public function getOutboundMessages(Request $request)
    {
        try {
            $user = auth()->user();
            
             if ($user->role === 'admin') {
                 $query = OutboundMessage::with(['student', 'teacher', 'admin'])
                    ->where(function($q) use ($user) {
                        $q->where('admin_id', $user->id)
                          ->orWhereNull('admin_id');  
                    })
                    ->orderBy('created_at', 'desc');
            } else {
                 $query = OutboundMessage::with(['student', 'teacher'])
                    ->where('teacher_id', $user->id)
                    ->orderBy('created_at', 'desc');
            }

             if ($request->has('student_id') && $request->student_id) {
                $query->where('student_id', $request->student_id);
            }

             if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

             if ($request->has('recipient_type') && $request->recipient_type) {
                $recipientType = $request->recipient_type;
                if ($recipientType === 'teacher') {
                    $query->where('recipient_type', 'teacher');
                } elseif ($recipientType === 'student') {
                    $query->where('student_id', '!=', null);
                } elseif ($recipientType === 'broadcast') {
                    $query->where('recipient_type', 'like', '%broadcast%');
                }
            }

             if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $messages = $query->paginate(20);

             if ($user->role === 'admin') {
                $stats = [
                    'sent' => OutboundMessage::where('admin_id', $user->id)->where('status', 'sent')->count(),
                    'failed' => OutboundMessage::where('admin_id', $user->id)->where('status', 'failed')->count()
                ];
            } else {
                $stats = [
                    'sent' => OutboundMessage::where('teacher_id', $user->id)->where('status', 'sent')->count(),
                    'failed' => OutboundMessage::where('teacher_id', $user->id)->where('status', 'failed')->count()
                ];
            }

            return response()->json([
                'success' => true,
                'messages' => $messages->items(),  
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total()
                ],
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching outbound messages', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching messages'
            ], 500);
        }
    }

    /**
     * Send attendance notification SMS
     */
    public function sendAttendanceNotification($student, $status, $time)
    {
        try {
            if (!$student->contact_person_contact) {
                Log::info('No contact number for student', [
                    'student_id' => $student->id,
                    'student_name' => $student->name
                ]);
                return false;
            }

            $message = $this->generateAttendanceMessage($student, $status, $time);
            
            $request = new Request([
                'number' => $student->contact_person_contact,
                'message' => $message,
                'student_id' => $student->id
            ]);

            $response = $this->sendSms($request);
            $responseData = json_decode($response->getContent(), true);

            return $responseData['status'] === 'success';

        } catch (Exception $e) {
            Log::error('Error sending attendance notification', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    
    private function sendToAllTeachers($message)
    {
        try {
             $teachers = User::where('role', 'teacher')
                ->whereNotNull('contact_number')
                ->where('contact_number', '!=', '')
                ->get();

            if ($teachers->isEmpty()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'No teachers with contact numbers found.'
                ], 404);
            }

            $recipients = [];
            foreach ($teachers as $teacher) {
                $contactNumber = trim($teacher->contact_number);
                if ($this->isValidPhoneNumber($contactNumber)) {
                    $recipients[] = [
                        'teacher' => $teacher,
                        'number' => $this->normalizePhoneNumber($contactNumber)
                    ];
                }
            }

            if (empty($recipients)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'No teachers with valid contact numbers found.'
                ], 404);
            }

            $successCount = 0;
            $failCount = 0;
            $messageIds = [];

            foreach ($recipients as $recipient) {
                $teacher = $recipient['teacher'];
                $normalizedNumber = $recipient['number'];

                $result = $this->smsService->sendSms($message, $normalizedNumber);

                if ($result['success']) {
                    $successCount++;
                    if (isset($result['message_id'])) {
                        $messageIds[] = $result['message_id'];
                    }
                } else {
                    $failCount++;
                }
            }

             $outboundMessage = OutboundMessage::create([
                'admin_id' => auth()->id(),
                'contact_number' => 'ALL_TEACHERS',
                'message' => $message,
                'status' => $successCount > 0 ? 'sent' : 'failed',
                'recipient_type' => 'teachers_broadcast',
                'recipient_count' => count($recipients)
            ]);

            Log::info('Admin SMS broadcast to teachers completed', [
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'total_recipients' => count($recipients),
                'outbound_message_id' => $outboundMessage->id
            ]);

            return response()->json([
                'status' => $successCount > 0 ? 'success' : 'fail',
                'message' => "SMS broadcast completed. Sent: {$successCount}, Failed: {$failCount}",
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'total_recipients' => count($recipients),
                'message_ids' => $messageIds,
                'outbound_message_id' => $outboundMessage->id
            ]);

        } catch (Exception $e) {
            Log::error('Admin SMS broadcast to teachers error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'An error occurred while sending SMS to teachers'
            ], 500);
        }
    }

 
    private function sendToSpecificTeacher($message, $teacherId)
    {
        try {
            $teacher = User::where('id', $teacherId)
                ->where('role', 'teacher')
                ->first();

            if (!$teacher) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Teacher not found.'
                ], 404);
            }

            if (empty($teacher->contact_number)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Teacher does not have a contact number.'
                ], 404);
            }

            $contactNumber = trim($teacher->contact_number);
            if (!$this->isValidPhoneNumber($contactNumber)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Teacher has invalid contact number format.'
                ], 422);
            }

            $normalizedNumber = $this->normalizePhoneNumber($contactNumber);
            $result = $this->smsService->sendSms($message, $normalizedNumber);

            $outboundMessage = OutboundMessage::create([
                'admin_id' => auth()->id(),
                'teacher_id' => $teacherId,
                'contact_number' => $normalizedNumber,
                'message' => $message,
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['status'] ?? 'pending',
                'recipient_type' => 'teacher',
                'recipient_count' => 1
            ]);

            if ($result['success']) {
                Log::info('Admin SMS to teacher sent successfully', [
                    'teacher_id' => $teacherId,
                    'message_id' => $result['message_id'],
                    'outbound_message_id' => $outboundMessage->id
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'SMS sent to teacher successfully',
                    'message_id' => $result['message_id'],
                    'outbound_message_id' => $outboundMessage->id
                ]);
            } else {
                Log::error('Admin SMS to teacher failed', [
                    'teacher_id' => $teacherId,
                    'error' => $result['error'] ?? 'Unknown error',
                    'outbound_message_id' => $outboundMessage->id
                ]);

                return response()->json([
                    'status' => 'fail',
                    'message' => 'Failed to send SMS to teacher: ' . ($result['error'] ?? 'Unknown error'),
                    'message_id' => $result['message_id'] ?? null,
                    'outbound_message_id' => $outboundMessage->id
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Admin SMS to teacher error', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'An error occurred while sending SMS to teacher'
            ], 500);
        }
    }

    /**
     * Generate attendance message
     */
    private function generateAttendanceMessage($student, $status, $time)
    {
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

  
    private function isValidPhoneNumber($number)
    {
         $cleaned = preg_replace('/[\s\-]/', '', $number);
        
         if (preg_match('/^\+639\d{9}$/', $cleaned)) {
            return true;
        }
        
         if (preg_match('/^09\d{9}$/', $cleaned)) {
            return true;
        }
        
        return false;
    }

 
    private function normalizePhoneNumber($number)
    {
         $cleaned = preg_replace('/[\s\-]/', '', $number);
        
         if (preg_match('/^09(\d{9})$/', $cleaned, $matches)) {
            return '+639' . $matches[1];
        }
        
         if (preg_match('/^\+639\d{9}$/', $cleaned)) {
            return $cleaned;
        }
        
        return $number;  
    }

   
    public function testGateway()
    {
        try {
            $isReachable = $this->smsService->isGatewayReachable();
            $gatewayInfo = $this->smsService->getGatewayInfo();

            return response()->json([
                'status' => 'success',
                'reachable' => $isReachable,
                'gateway_info' => $gatewayInfo
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Error testing gateway: ' . $e->getMessage()
            ], 500);
        }
    }
}
