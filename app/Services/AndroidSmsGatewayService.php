<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\OutboundMessage;
use Carbon\Carbon;
use Exception;

class AndroidSmsGatewayService
{
    protected $gatewayUrl;
    protected $login;
    protected $password;
    protected $timeout;

    public function __construct()
    {
        $this->gatewayUrl = config('sms.gateway_url');
        $this->login = config('sms.login');
        $this->password = config('sms.password');
        $this->timeout = config('sms.timeout', 30);
    }

    /**
     * Send SMS with optional sender ID support
     */
    public function sendSms($text, $recipients, $senderId = null, $metadata = [])
    {
        try {
             $senderId = $senderId ?? config('sms.sender_id', 'Scan-to-notify');
            
             if (is_string($recipients)) {
                $recipients = [$recipients];
            }

             $validRecipients = [];
            foreach ($recipients as $recipient) {
                $normalizedNumber = $this->normalizePhoneNumber($recipient);
                if ($this->isValidPhoneNumber($normalizedNumber)) {
                     if ($this->canSendMessage($normalizedNumber)) {
                        $validRecipients[] = $normalizedNumber;
                    } else {
                        Log::info('Message rate limited', [
                            'number' => $normalizedNumber,
                            'delay_seconds' => config('sms.message_delay_seconds', 60)
                        ]);
                    }
                } else {
                    Log::warning('Invalid phone number skipped', ['number' => $recipient]);
                }
            }

            if (empty($validRecipients)) {
                throw new Exception('No valid recipients provided or all recipients are rate limited');
            }

             $requestData = [
                'textMessage' => [
                    'text' => $text,
                    'senderId' => $senderId
                ],
                'phoneNumbers' => $validRecipients,
            ];

            Log::info('Sending SMS via Android Gateway', [
                'recipients' => $validRecipients,
                'message_length' => strlen($text),
                'sender_id' => $senderId,
                'gateway_url' => $this->gatewayUrl,
                'full_request_data' => $requestData
            ]);

             $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->login, $this->password)
                ->post($this->gatewayUrl . '/message', $requestData);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['id'] ?? uniqid('sms_');
                
                 foreach ($validRecipients as $recipient) {
                    $recipientMetadata = array_merge($metadata, [
                        'message' => $text
                    ]);
                    $this->updateLastSentTime($recipient, $messageId, $responseData['status'] ?? 'sent', $recipientMetadata);
                }
                
                Log::info('SMS sent successfully', [
                    'response' => $responseData,
                    'recipients' => $validRecipients,
                    'sender_id' => $senderId
                ]);

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'status' => $responseData['status'] ?? 'sent',
                    'data' => $responseData,
                    'recipients' => $validRecipients
                ];
            } else {
                $errorMessage = 'HTTP ' . $response->status() . ': ' . $response->body();
                
                Log::error('SMS sending failed', [
                    'error' => $errorMessage,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'sender_id' => $senderId
                ]);

                return [
                    'success' => false,
                    'message_id' => null,
                    'status' => 'failed',
                    'error' => $errorMessage,
                    'recipients' => $validRecipients
                ];
            }

        } catch (Exception $e) {
            Log::error('SMS service error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'recipients' => $recipients ?? null,
                'sender_id' => $senderId ?? 'undefined'
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'recipients' => $recipients
            ];
        }
    }

  
    public function getStatus($messageId)
    {
        try {
            Log::info('Checking SMS status', ['message_id' => $messageId]);

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->login, $this->password)
                ->get($this->gatewayUrl . '/messages/' . $messageId);

            if ($response->successful()) {
                $statusData = $response->json();
                
                Log::info('SMS status retrieved', [
                    'message_id' => $messageId,
                    'status' => $statusData
                ]);

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'status' => $statusData['status'] ?? 'unknown',
                    'data' => $statusData
                ];
            } else {
                $errorMessage = 'HTTP ' . $response->status() . ': ' . $response->body();
                
                Log::error('Failed to get SMS status', [
                    'message_id' => $messageId,
                    'error' => $errorMessage
                ]);

                return [
                    'success' => false,
                    'message_id' => $messageId,
                    'status' => 'unknown',
                    'error' => $errorMessage
                ];
            }

        } catch (Exception $e) {
            Log::error('SMS status check error', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message_id' => $messageId,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
 
    protected function isValidPhoneNumber($number)
    {
        if (!config('sms.validate_numbers', true)) {
            return true;
        }

         $cleaned = preg_replace('/[\s\-]/', '', $number);
        
        if (preg_match('/^\+639\d{9}$/', $cleaned)) {
            return true;
        }
        
        if (preg_match('/^09\d{9}$/', $cleaned)) {
            return true;
        }
        
        if (preg_match('/^\d{3,6}$/', $cleaned)) {
            return true;
        }
        
        return false;
    }

 
    protected function normalizePhoneNumber($number)
    {
         $cleaned = preg_replace('/[\s\-]/', '', $number);
        
         if (preg_match('/^\d{3,6}$/', $cleaned)) {
            return $cleaned;
        }
        
         if (preg_match('/^09(\d{9})$/', $cleaned, $matches)) {
            return '+639' . $matches[1];
        }
        
         if (preg_match('/^\+639\d{9}$/', $cleaned)) {
            return $cleaned;
        }
        
         return $number;
    }

  
    public function isGatewayReachable()
    {
        try {
            $response = Http::timeout(5)
                ->withBasicAuth($this->login, $this->password)
                ->get($this->gatewayUrl);

            return $response->successful();
        } catch (Exception $e) {
            Log::warning('SMS Gateway not reachable', ['error' => $e->getMessage()]);
            return false;
        }
    }

  
    public function getGatewayInfo()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->login, $this->password)
                ->get($this->gatewayUrl);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $response->status()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

   
    protected function canSendMessage($number)
    {
         if (!config('sms.enable_rate_limiting', true)) {
            return true;
        }

        $delaySeconds = config('sms.message_delay_seconds', 60);
        $cutoffTime = Carbon::now()->subSeconds($delaySeconds);

         $recentMessage = OutboundMessage::where('contact_number', $number)
            ->where('last_sent_at', '>', $cutoffTime)
            ->orderBy('last_sent_at', 'desc')
            ->first();

        return $recentMessage === null;
    }

    /**
     * Get rate limiting status for a specific number
     */
    public function getRateLimitStatus($number)
    {
        $normalizedNumber = $this->normalizePhoneNumber($number);
        
        if (!config('sms.enable_rate_limiting', true)) {
            return [
                'can_send' => true,
                'rate_limiting_enabled' => false,
                'message' => 'Rate limiting is disabled'
            ];
        }

        $delaySeconds = config('sms.message_delay_seconds', 60);
        $cutoffTime = Carbon::now()->subSeconds($delaySeconds);

        $recentMessage = OutboundMessage::where('contact_number', $normalizedNumber)
            ->where('last_sent_at', '>', $cutoffTime)
            ->orderBy('last_sent_at', 'desc')
            ->first();

        if ($recentMessage === null) {
            return [
                'can_send' => true,
                'rate_limiting_enabled' => true,
                'message' => 'Ready to send'
            ];
        }

        $timeRemaining = $delaySeconds - Carbon::now()->diffInSeconds($recentMessage->last_sent_at);
        
        return [
            'can_send' => false,
            'rate_limiting_enabled' => true,
            'time_remaining_seconds' => max(0, $timeRemaining),
            'last_sent_at' => $recentMessage->last_sent_at,
            'message' => "Must wait {$timeRemaining} more seconds"
        ];
    }

    /**
     * Update the last sent time for a contact number
     */
    protected function updateLastSentTime($number, $messageId = null, $status = 'sent', $additionalData = [])
    {
        $now = Carbon::now();
        
         $recentMessage = OutboundMessage::where('contact_number', $number)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentMessage && $recentMessage->created_at->diffInMinutes($now) < 5) {
             $recentMessage->update([
                'last_sent_at' => $now,
                'message_id' => $messageId ?: $recentMessage->message_id,
                'status' => $status
            ]);
        } else {
             $messageData = array_merge([
                'teacher_id' => auth()->id() ?? 1,  
                'contact_number' => $number,
                'message_id' => $messageId,
                'status' => $status,
                'last_sent_at' => $now,
                'recipient_type' => 'individual',
                'recipient_count' => 1
            ], $additionalData);

            OutboundMessage::create($messageData);
        }
    }
}
