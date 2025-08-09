<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token',
        'teacher_id',
        'semester_id',
        'session_name',
        'status',
        'started_at',
        'closed_at',
        'access_count',
        'access_log',
        'attendance_count',
        'duration_minutes',
        'last_activity_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'access_log' => 'array'
    ];

    /**
     * Generate a unique session token
     */
    public static function generateToken()
    {
        do {
            $token = Str::random(32);
        } while (self::where('session_token', $token)->exists());
        
        return $token;
    }

    /**
     * Create or get active session - ONLY ONE ACTIVE SESSION PER TEACHER
     * No expiry - session stays active until manually closed
     */
    public static function createOrGetActiveSession($teacherId, $semesterId, $sessionName = null)
    {
        // Check if there's already an active session for this teacher and semester
        $existingSession = self::where('teacher_id', $teacherId)
                              ->where('semester_id', $semesterId)
                              ->where('status', 'active')
                              ->first();
                              
        if ($existingSession) {
            return $existingSession;
        }

        // AUTOMATICALLY CLOSE ALL OTHER ACTIVE SESSIONS for this teacher
        // This ensures only one session is active at a time
        $closedCount = self::where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->update([
                'status' => 'closed',
                'closed_at' => Carbon::now('Asia/Manila'),
                'updated_at' => Carbon::now('Asia/Manila')
            ]);

        // Log if any sessions were automatically closed
        if ($closedCount > 0) {
            \Log::info('Automatically closed previous active sessions', [
                'teacher_id' => $teacherId,
                'closed_sessions_count' => $closedCount,
                'reason' => 'Creating new session'
            ]);
        }

        $token = self::generateToken();
        $now = Carbon::now('Asia/Manila');
        
        // Create new session with NO expiry
        return self::create([
            'session_token' => $token,
            'teacher_id' => $teacherId,
            'semester_id' => $semesterId,
            'session_name' => $sessionName ?: 'Attendance Session - ' . $now->format('M j, Y g:i A'),
            'started_at' => $now,
            'last_activity_at' => $now,
            'status' => 'active',
            'access_log' => [],
            'attendance_count' => 0,
            'access_count' => 0
        ]);
    }

    /**
     * Legacy methods - redirect to new method
     */
    public static function createSession($teacherId, $semesterId, $expiresInMinutes = null, $sessionName = null)
    {
        return self::createOrGetActiveSession($teacherId, $semesterId, $sessionName);
    }

    public static function createDailySession($teacherId, $semesterId, $sessionName = null)
    {
        return self::createOrGetActiveSession($teacherId, $semesterId, $sessionName);
    }

    public static function getTodaysSession($teacherId, $semesterId)
    {
        return self::createOrGetActiveSession($teacherId, $semesterId);
    }

    /**
     * Check if session is valid (only status matters now)
     */
    public function isValid()
    {
        return $this->status === 'active';
    }

    /**
     * Sessions never expire automatically now
     */
    public function isExpired()
    {
        // Check if status is not active
        if ($this->status !== 'active') {
            return true;
        }

        // Check if session is from a previous day (daily expiry)
        $now = Carbon::now('Asia/Manila');
        $today = $now->format('Y-m-d');
        $sessionDate = $this->started_at ? $this->started_at->format('Y-m-d') : null;

        if ($sessionDate && $sessionDate < $today) {
            // Mark as expired if from previous day
            $this->update(['status' => 'expired']);
            return true;
        }

        // Check if outside access hours (5 AM to 6 PM)
        $currentHour = $now->hour;
        if ($currentHour < 5 || $currentHour >= 18) {
            return true; // Don't auto-expire, just return true for access check
        }

        return false;
    }

    /**
     * Auto-expire old sessions (daily cleanup)
     */
    public static function expireOldSessions()
    {
        $today = Carbon::now('Asia/Manila')->format('Y-m-d');
        
        $expired = self::where('status', 'active')
            ->whereDate('started_at', '<', $today)
            ->update(['status' => 'expired']);
            
        return $expired;
    }

    /**
     * Check if attendance is allowed based on semester's time periods
     */
    public function isAttendanceAllowed()
    {
        // Check if session is active
        if ($this->status !== 'active') {
            return [
                'allowed' => false,
                'period' => null,
                'message' => 'Session is not active'
            ];
        }

        $now = Carbon::now('Asia/Manila');
        $semester = $this->semester;
        
        if (!$semester) {
            return [
                'allowed' => false,
                'period' => null,
                'message' => 'Semester not found'
            ];
        }
        
        
        $periods = [
            'morning_time_in' => [
                'start' => $semester->am_time_in_start ? Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->am_time_in_start) : null,
                'end' => $semester->am_time_in_end ? Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->am_time_in_end) : null,
                'type' => 'Time In'
            ],
            'afternoon_time_out' => [
                'start' => $semester->pm_time_out_start ? Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->pm_time_out_start) : null,
                'end' => $semester->pm_time_out_end ? Carbon::today('Asia/Manila')->setTimeFromTimeString($semester->pm_time_out_end) : null,
                'type' => 'Time Out'
            ]
        ];
        
        foreach ($periods as $periodKey => $period) {
            if ($period['start'] && $period['end'] && $now->between($period['start'], $period['end'])) {
                return [
                    'allowed' => true,
                    'period' => $periodKey,
                    'period_name' => ucfirst(str_replace('_', ' ', $periodKey)),
                    'period_type' => $period['type'],
                    'start_time' => $period['start']->format('g:i A'),
                    'end_time' => $period['end']->format('g:i A'),
                    'time_remaining' => $period['end']->diffInMinutes($now)
                ];
            }
        }
        
        return [
            'allowed' => false,
            'period' => null,
            'next_period' => $this->getNextPeriod($periods, $now)
        ];
    }
    
    
    private function getNextPeriod($periods, $now)
    {
        foreach ($periods as $periodKey => $period) {
            if ($period['start'] && $period['end'] && $now->lessThan($period['start'])) {
                return [
                    'period' => $periodKey,
                    'period_name' => ucfirst(str_replace('_', ' ', $periodKey)),
                    'period_type' => $period['type'],
                    'start_time' => $period['start']->format('g:i A'),
                    'end_time' => $period['end']->format('g:i A'),
                    'starts_in' => $now->diffInMinutes($period['start'])
                ];
            }
        }
        
         $semester = $this->semester;
        if ($semester && $semester->am_time_in_start) {
            $tomorrowMorning = Carbon::tomorrow('Asia/Manila')->setTimeFromTimeString($semester->am_time_in_start);
            $tomorrowMorningEnd = Carbon::tomorrow('Asia/Manila')->setTimeFromTimeString($semester->am_time_in_end ?: '08:30:00');
            
            return [
                'period' => 'morning_time_in',
                'period_name' => 'Morning Time In (Tomorrow)',
                'period_type' => 'Time In',
                'start_time' => $tomorrowMorning->format('g:i A'),
                'end_time' => $tomorrowMorningEnd->format('g:i A'),
                'starts_in' => $now->diffInMinutes($tomorrowMorning)
            ];
        }
        
        return null;
    }
    
    /**
     * Close session manually - simple status change
     */
    public function close()
    {
        $now = Carbon::now('Asia/Manila');
        $duration = $this->started_at ? $this->started_at->diffInMinutes($now) : 0;
        
        $this->update([
            'status' => 'closed',
            'closed_at' => $now,
            'duration_minutes' => $duration
        ]);
    }

    /**
     * Record attendance scan
     */
    public function recordAttendance()
    {
        $this->increment('attendance_count');
        $this->update(['last_activity_at' => Carbon::now('Asia/Manila')]);
    }

    /**
     * Log access to session - simple update
     */
    public function logAccess($ipAddress, $userAgent = null)
    {
        $currentLog = $this->access_log ?: [];
        
        $logEntry = [
            'timestamp' => Carbon::now('Asia/Manila')->toISOString(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ];
        
        $currentLog[] = $logEntry;
        
        // Keep only last 50 access logs to prevent bloat
        if (count($currentLog) > 50) {
            $currentLog = array_slice($currentLog, -50);
        }
        
        $this->update([
            'access_count' => $this->access_count + 1,
            'access_log' => $currentLog,
            'last_activity_at' => Carbon::now('Asia/Manila')
        ]);
    }

    
    public function getFormattedDurationAttribute()
    {
        if ($this->duration_minutes) {
            $hours = floor($this->duration_minutes / 60);
            $minutes = $this->duration_minutes % 60;
            
            if ($hours > 0) {
                return "{$hours}h {$minutes}m";
            }
            return "{$minutes}m";
        }
        
        if ($this->started_at && $this->status === 'active') {
            $currentDuration = $this->started_at->diffInMinutes(Carbon::now());
            $hours = floor($currentDuration / 60);
            $minutes = $currentDuration % 60;
            
            if ($hours > 0) {
                return "{$hours}h {$minutes}m";
            }
            return "{$minutes}m";
        }
        
        return '-';
    }

    
    public function getTimeRemaining()
    {
        $attendanceStatus = $this->isAttendanceAllowed();
        
        if ($attendanceStatus['allowed']) {
            return $attendanceStatus['time_remaining'];
        }
        
        return 0; 
    }

    
    public function getCurrentPeriodInfo()
    {
        return $this->isAttendanceAllowed();
    }

    
    public function getFormattedTimeRemainingAttribute()
    {
        $remainingMinutes = $this->getTimeRemaining();
        
        if ($remainingMinutes === null) {
            return 'No Active Period';
        }
        
        if ($remainingMinutes <= 0) {
            return 'Period Ended';
        }

        $hours = floor($remainingMinutes / 60);
        $minutes = $remainingMinutes % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m remaining";
        }
        
        return "{$minutes}m remaining";
    }

    /**
     * Get the public attendance URL
     */
    public function getPublicUrl()
    {
        return route('attendance.public', ['token' => $this->session_token]);
    }

    
    public function getNameAttribute()
    {
        return $this->session_name;
    }

    
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    
    public function scopeInactive($query)
    {
        return $query->where('status', '!=', 'active');
    }

    
    public function scopeToday($query)
    {
        return $query->whereDate('started_at', Carbon::today('Asia/Manila'));
    }

    
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('started_at', $date);
    }
}
