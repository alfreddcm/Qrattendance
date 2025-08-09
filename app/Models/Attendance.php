<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'semester_id',
        'student_id',
        'school_id',
        'date',
        'time_in_am',
        'time_out_am',
        'time_in_pm',
        'time_out_pm',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForSemester($query, $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeWithMorningAttendance($query)
    {
        return $query->whereNotNull('time_in_am');
    }

    public function scopeWithAfternoonAttendance($query)
    {
        return $query->whereNotNull('time_in_pm');
    }

    public function scopeWithFullDay($query)
    {
        return $query->whereNotNull('time_in_am')
                    ->whereNotNull('time_out_am')
                    ->whereNotNull('time_in_pm')
                    ->whereNotNull('time_out_pm');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('student', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function isLateArrival($thresholdTime = '08:00')
    {
        return $this->time_in_am && $this->time_in_am > $thresholdTime;
    }

    public function isEarlyDeparture($thresholdTime = '16:00')
    {
        return $this->time_out_pm && $this->time_out_pm < $thresholdTime;
    }

    public function getAttendanceStatusAttribute()
    {
        if ($this->time_in_am && $this->time_out_am && $this->time_in_pm && $this->time_out_pm) {
            return 'Full Day';
        } elseif ($this->time_in_am && $this->time_out_am) {
            return 'Morning Only';
        } elseif ($this->time_in_pm && $this->time_out_pm) {
            return 'Afternoon Only';
        } elseif ($this->time_in_am || $this->time_in_pm) {
            return 'Partial';
        }
        return 'No Attendance';
    }

    public function getTotalHoursAttribute()
    {
        $hours = 0;
        
        if ($this->time_in_am && $this->time_out_am) {
            $morning_in = \Carbon\Carbon::parse($this->time_in_am);
            $morning_out = \Carbon\Carbon::parse($this->time_out_am);
            $hours += $morning_out->diffInHours($morning_in);
        }
        
        if ($this->time_in_pm && $this->time_out_pm) {
            $afternoon_in = \Carbon\Carbon::parse($this->time_in_pm);
            $afternoon_out = \Carbon\Carbon::parse($this->time_out_pm);
            $hours += $afternoon_out->diffInHours($afternoon_in);
        }
        
        return $hours;
    }

// time format
     public function getTimeInAmFormattedAttribute()
    {
        return $this->time_in_am ? Carbon::createFromFormat('H:i:s', $this->time_in_am)->format('g:i:s A') : null;
    }

    public function getTimeOutAmFormattedAttribute()
    {
        return $this->time_out_am ? Carbon::createFromFormat('H:i:s', $this->time_out_am)->format('g:i:s A') : null;
    }

    public function getTimeInPmFormattedAttribute()
    {
        return $this->time_in_pm ? Carbon::createFromFormat('H:i:s', $this->time_in_pm)->format('g:i:s A') : null;
    }

    public function getTimeOutPmFormattedAttribute()
    {
        return $this->time_out_pm ? Carbon::createFromFormat('H:i:s', $this->time_out_pm)->format('g:i:s A') : null;
    }

    // Helper method to format any time field
    public static function formatTime($time)
    {
        if (!$time) return null;
        
        try {
            return Carbon::createFromFormat('H:i:s', $time)->format('g:i:s A');
        } catch (\Exception $e) {
            return $time; // Return original if parsing fails
        }
    }
}