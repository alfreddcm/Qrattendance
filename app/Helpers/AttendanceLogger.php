<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceLogger
{
    /**
     * Log attendance session events
     */
    public static function logSession($action, $sessionId = null, $additionalData = [])
    {
        $logData = [
            'timestamp' => Carbon::now()->toISOString(),
            'action' => $action,
            'session_id' => $sessionId,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent')
        ];
        
        if (!empty($additionalData)) {
            $logData = array_merge($logData, $additionalData);
        }
        
        Log::channel('attendance')->info($action, $logData);
    }
    
    /**
     * Log attendance QR scan events
     */
    public static function logQrScan($action, $studentId = null, $sessionId = null, $additionalData = [])
    {
        $logData = [
            'timestamp' => Carbon::now()->toISOString(),
            'action' => $action,
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent')
        ];
        
        if (!empty($additionalData)) {
            $logData = array_merge($logData, $additionalData);
        }
        
        Log::channel('attendance')->info($action, $logData);
    }
    
    /**
     * Log errors with context
     */
    public static function logError($action, $error, $additionalData = [])
    {
        $logData = [
            'timestamp' => Carbon::now()->toISOString(),
            'action' => $action,
            'error' => $error,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent')
        ];
        
        if (!empty($additionalData)) {
            $logData = array_merge($logData, $additionalData);
        }
        
        Log::channel('attendance')->error($action, $logData);
    }
}
