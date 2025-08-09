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
        'expires_at',
        'started_at',
        'closed_at',
        'access_count',
        'access_log',
        'attendance_count',
        'duration_minutes',
        'last_activity_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'access_log' => 'array'
    ];

    /**
     * Override update method to protect expires_at from accidental changes
     */
    public function update(array $attributes = [], array $options = [])
    {
        // Remove expires_at from updates unless explicitly allowed
        if (isset($attributes['expires_at']) && !isset($options['allow_expires_at_update'])) {
            unset($attributes['expires_at']);
        }
        
        return parent::update($attributes, $options);
    }

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
     * Create or get today's automatic daily session
     */
    public static function createSession($teacherId, $semesterId, $expiresInMinutes = null, $sessionName = null)
    {
        $today = Carbon::today('Asia/Manila');
        
        // Check if there's already a session for today
        $existingSession = self::where('teacher_id', $teacherId)
                              ->where('semester_id', $semesterId)
                              ->where('status', 'active')
                              ->whereDate('started_at', $today)
                              ->first();
                              
        if ($existingSession) {
            return $existingSession;
        }

        $token = self::generateToken();
        $now = Carbon::now('Asia/Manila');
        $endOfDay = Carbon::today('Asia/Manila')->addDay(); // Expires at midnight
        
        return self::create([
            'session_token' => $token,
            'teacher_id' => $teacherId,
            'semester_id' => $semesterId,
            'session_name' => $sessionName ?: 'Daily Attendance - ' . $today->format('M j, Y'),
            'expires_at' => $endOfDay,  // Set expiry to end of day
            'started_at' => $now,
            'last_activity_at' => $now,
            'status' => 'active',
            'access_log' => [],
            'attendance_count' => 0
        ]);
    }

    /**
     * Get or create today's session automatically
     */
    public static function getTodaysSession($teacherId, $semesterId)
    {
        return self::createSession($teacherId, $semesterId);
    }

    public function isValid()
    {
        return $this->status === 'active';
    }

    /**
     * Check if session is expired
     */
    public function isExpired()
    {
        if ($this->status !== 'active') {
            return true;
        }
        
        if ($this->expires_at && Carbon::now('Asia/Manila')->greaterThan($this->expires_at)) {
            return true;
        }
        
        return false;
    }

    /**
     * Auto-expire sessions that have passed their expires_at time
     */
    public static function expireOldSessions()
    {
        $now = Carbon::now('Asia/Manila');
        
        // Expire sessions that have passed their expires_at time
        self::where('status', 'active')
            ->where('expires_at', '<', $now)
            ->update([
                'status' => 'expired',
                'closed_at' => $now
            ]);
    }

    /**
     * Check if attendance is allowed based on semester's time periods
     */
    public function isAttendanceAllowed()
    {
        // Check if session is active and it's the same day
        if ($this->status !== 'active') {
            return [
                'allowed' => false,
                'period' => null,
                'message' => 'Session is not active'
            ];
        }

        // Check if it's still the same day as session creation
        $sessionDate = Carbon::parse($this->started_at)->timezone('Asia/Manila')->toDateString();
        $currentDate = Carbon::now('Asia/Manila')->toDateString();
        
        if ($sessionDate !== $currentDate) {
            return [
                'allowed' => false,
                'period' => null,
                'message' => 'Session has expired (new day)'
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
        
        // Get semester's time periods
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
    
    /**
     * Get next attendance period based on semester settings
     */
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
     * Close session manually
     */
    public function close()
    {
        $duration = $this->started_at ? $this->started_at->diffInMinutes(Carbon::now()) : 0;
        
        $this->update([
            'status' => 'closed',
            'closed_at' => Carbon::now(),
            'duration_minutes' => $duration
        ]);
    }

    /**
     * Mark session as expired
     */
    public function expire()
    {
        $duration = $this->started_at ? $this->started_at->diffInMinutes(Carbon::now()) : 0;
        
        $this->update([
            'status' => 'expired',
            'closed_at' => Carbon::now(),
            'duration_minutes' => $duration
        ]);
    }

    /**
     * Record attendance scan
     */
    public function recordAttendance()
    {
        $this->increment('attendance_count');
        $this->update(['last_activity_at' => Carbon::now()]);
    }

    /**
     * Get formatted duration
     */
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

    /**
     * Get current attendance period status
     */
    public function getTimeRemaining()
    {
        $attendanceStatus = $this->isAttendanceAllowed();
        
        if ($attendanceStatus['allowed']) {
            return $attendanceStatus['time_remaining'];
        }
        
        return 0; // No active period
    }

    /**
     * Get current period information
     */
    public function getCurrentPeriodInfo()
    {
        return $this->isAttendanceAllowed();
    }

    /**
     * Get formatted time remaining for current period (not session expiry)
     */
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
     * Log access to session - FIXED to not update expires_at or started_at
     */
    public function logAccess($ipAddress, $userAgent = null)
    {
        $currentLog = $this->access_log ?: [];
        
        $logEntry = [
            'timestamp' => Carbon::now()->toISOString(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ];
        
        $currentLog[] = $logEntry;
        
        // Keep only last 50 access logs to prevent bloat
        if (count($currentLog) > 50) {
            $currentLog = array_slice($currentLog, -50);
        }
        
        // Only update access count and log, do NOT update started_at or expires_at
        $this->update([
            'access_count' => $this->access_count + 1,
            'access_log' => $currentLog
        ]);
    }

    /**
     * Get the public attendance URL
     */
    public function getPublicUrl()
    {
        return route('attendance.public', ['token' => $this->session_token]);
    }

    /**
     * Accessor for name attribute (maps to session_name)
     */
    public function getNameAttribute()
    {
        return $this->session_name;
    }

    /**
     * Relationships
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Scope for active sessions (permanent daily links)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive sessions
     */
    public function scopeInactive($query)
    {
        return $query->where('status', '!=', 'active');
    }

    /**
     * Scope for today's sessions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('started_at', Carbon::today('Asia/Manila'));
    }

    /**
     * Scope for sessions by date
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('started_at', $date);
    }
}
