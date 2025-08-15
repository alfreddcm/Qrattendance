<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
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
    public function sendSms($text, $recipients, $senderId = null)
    {
        try {
            // Get sender ID from parameter, config, or default
            $senderId = $senderId ?? config('sms.sender_id', 'Scan-to-notify');
            
             if (is_string($recipients)) {
                $recipients = [$recipients];
            }

             $validRecipients = [];
            foreach ($recipients as $recipient) {
                $normalizedNumber = $this->normalizePhoneNumber($recipient);
                if ($this->isValidPhoneNumber($normalizedNumber)) {
                    $validRecipients[] = $normalizedNumber;
                } else {
                    Log::warning('Invalid phone number skipped', ['number' => $recipient]);
                }
            }

            if (empty($validRecipients)) {
                throw new Exception('No valid recipients provided');
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
                
                Log::info('SMS sent successfully', [
                    'response' => $responseData,
                    'recipients' => $validRecipients,
                    'sender_id' => $senderId
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseData['id'] ?? uniqid('sms_'),
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
        // Remove spaces and dashes
        $cleaned = preg_replace('/[\s\-]/', '', $number);
        
        // If it's a short code (3-6 digits), return as-is
        if (preg_match('/^\d{3,6}$/', $cleaned)) {
            return $cleaned;
        }
        
        // Convert Philippine 09 format to +63 format
        if (preg_match('/^09(\d{9})$/', $cleaned, $matches)) {
            return '+639' . $matches[1];
        }
        
        // If already in +63 format, return as-is
        if (preg_match('/^\+639\d{9}$/', $cleaned)) {
            return $cleaned;
        }
        
        // Return original number if no pattern matches
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
}
