<?php

namespace App\Http\Controllers;

use App\Models\OutboundMessage;
use App\Models\Student;
use App\Services\AndroidSmsGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class MessageApiController extends Controller
{
    protected $smsService;

    public function __construct(AndroidSmsGatewayService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send SMS message via Android SMS Gateway
     */
    public function sendSms(Request $request)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'number' => 'required|string',
                'message' => 'required|string|max:1000',
                'student_id' => 'nullable|exists:students,id',
                'send_to_all' => 'boolean'
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
            $sendToAll = $request->input('send_to_all', false);

            // Handle send to all parents
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

            $senderId = config('sms.use_sender_id') ? config('sms.sender_id') : null;

            $result = $this->smsService->sendSms($message, $normalizedNumber, $senderId);

            // Store to database
            $outboundMessage = OutboundMessage::create([
                'teacher_id' => auth()->id(),
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

    /**
     * Send SMS to all parents of students in teacher's class
     */
    private function sendToAllParents($message)
    {
        try {
            // Get all students with parent contact numbers for the current teacher
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

            // First, collect all valid recipients to get the total count
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

            // Create one record for the broadcast message
            $broadcastResult = [
                'success_count' => 0,
                'fail_count' => 0,
                'message_ids' => []
            ];

            // Send to each parent
            foreach ($recipients as $recipient) {
                $student = $recipient['student'];
                $normalizedNumber = $recipient['number'];

                $result = $this->smsService->sendSms($message, $normalizedNumber, $senderId);

                if ($result['success']) {
                    $successCount++;
                    if (isset($result['message_id'])) {
                        $broadcastResult['message_ids'][] = $result['message_id'];
                    }
                } else {
                    $failCount++;
                }
            }

            // Store one record for the entire broadcast
            OutboundMessage::create([
                'teacher_id' => auth()->id(),
                'student_id' => null, // No specific student for broadcast
                'contact_number' => 'broadcast', // Indicate this is a broadcast
                'message' => $message,
                'message_id' => json_encode($broadcastResult['message_ids']), // Store all message IDs
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

            // Get status from SMS gateway
            $statusResult = $this->smsService->getStatus($outboundMessage->message_id);

            // Update the outbound message status
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

    /**
     * Get outbound messages history
     */
    public function getOutboundMessages(Request $request)
    {
        try {
            $query = OutboundMessage::with(['student', 'teacher'])
                ->where('teacher_id', auth()->id())
                ->orderBy('created_at', 'desc');

             if ($request->has('student_id') && $request->student_id) {
                $query->where('student_id', $request->student_id);
            }

             if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

             if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $messages = $query->paginate(20);

            return response()->json([
                'success' => true,
                'messages' => $messages->items(),  
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total()
                ],
                'stats' => [
                    'sent' => OutboundMessage::where('teacher_id', auth()->id())->where('status', 'sent')->count(),
                    'failed' => OutboundMessage::where('teacher_id', auth()->id())->where('status', 'failed')->count()
                ]
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

    /**
     * Generate attendance message
     */
    private function generateAttendanceMessage($student, $status, $time)
    {
        $statusText = strtoupper($status);
        $timeFormatted = \Carbon\Carbon::parse($time)->format('g:i A');
        
        return "Your child {$student->name} was marked {$statusText} today at {$timeFormatted}.";
    }

    /**
     * Validate Philippine phone number format
     */
    private function isValidPhoneNumber($number)
    {
        // Remove spaces and dashes
        $cleaned = preg_replace('/[\s\-]/', '', $number);
        
        // Check for +63 format (13 digits total)
        if (preg_match('/^\+639\d{9}$/', $cleaned)) {
            return true;
        }
        
        // Check for 09 format (11 digits total)
        if (preg_match('/^09\d{9}$/', $cleaned)) {
            return true;
        }
        
        return false;
    }

    /**
     * Normalize phone number to +63 format
     */
    private function normalizePhoneNumber($number)
    {
        // Remove spaces and dashes
        $cleaned = preg_replace('/[\s\-]/', '', $number);
        
        // If starts with 09, convert to +63
        if (preg_match('/^09(\d{9})$/', $cleaned, $matches)) {
            return '+639' . $matches[1];
        }
        
        // If already in +63 format, return as is
        if (preg_match('/^\+639\d{9}$/', $cleaned)) {
            return $cleaned;
        }
        
        return $number; // Return original if no match
    }

    /**
     * Test SMS gateway connectivity
     */
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
