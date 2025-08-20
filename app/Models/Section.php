<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gradelevel',
        'teacher_id',
        'semester_id',
        'am_time_in_start',
        'am_time_in_end',
        'am_time_out_start',
        'am_time_out_end',
        'pm_time_in_start',
        'pm_time_in_end',
        'pm_time_out_start',
        'pm_time_out_end',
    ];

    /**
     * Relationship: Section belongs to a Semester
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    /**
     * Relationship: Section belongs to a Teacher (User)
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Relationship: Section has many Students
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'section_id');
    }

    /**
     * Relationship: Section has many Attendances through Students
     */
    public function attendances()
    {
        return $this->hasManyThrough(Attendance::class, Student::class, 'section_id', 'student_id');
    }

    /**
     * Check if current time is within valid attendance period for this section
     */
    public function isValidAttendanceTime($time = null, $period = null)
    {
        $semester = $this->semester;
        if (!$semester) {
            return false;
        }

        // First check if time is within semester periods
        if (!$semester->isWithinValidPeriod($time)) {
            return false;
        }

        $time = $time ? Carbon::parse($time) : Carbon::now();
        $timeStr = $time->format('H:i:s');
        
        // If period is specified, check only that period
        if ($period) {
            if ($period === 'am') {
                return $this->isTimeInRange($timeStr, $this->am_time_in_start, $this->am_time_in_end) ||
                       $this->isTimeInRange($timeStr, $this->am_time_out_start, $this->am_time_out_end);
            } else {
                return $this->isTimeInRange($timeStr, $this->pm_time_in_start, $this->pm_time_in_end) ||
                       $this->isTimeInRange($timeStr, $this->pm_time_out_start, $this->pm_time_out_end);
            }
        }
        
        // Check all periods
        return $this->isTimeInRange($timeStr, $this->am_time_in_start, $this->am_time_in_end) ||
               $this->isTimeInRange($timeStr, $this->am_time_out_start, $this->am_time_out_end) ||
               $this->isTimeInRange($timeStr, $this->pm_time_in_start, $this->pm_time_in_end) ||
               $this->isTimeInRange($timeStr, $this->pm_time_out_start, $this->pm_time_out_end);
    }

    /**
     * Get attendance status based on time and period
     */
    public function getAttendanceStatus($time, $period = 'am')
    {
        $time = Carbon::parse($time)->format('H:i:s');
        
        if ($period === 'am') {
            $startTime = $this->am_time_in_start;
            $endTime = $this->am_time_in_end;
        } else {
            $startTime = $this->pm_time_in_start;
            $endTime = $this->pm_time_in_end;
        }
        
        if (!$startTime || !$endTime) {
            return 'Unknown';
        }
        
        // Convert to comparable format
        $checkTime = Carbon::createFromFormat('H:i:s', $time);
        $start = Carbon::createFromFormat('H:i:s', $startTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);
        
        // Calculate grace period (10 minutes before start)
        $earlyTime = $start->copy()->subMinutes(10);
        
        if ($checkTime->lt($earlyTime)) {
            return 'Early';
        } elseif ($checkTime->between($earlyTime, $start)) {
            return 'On Time';
        } elseif ($checkTime->between($start, $end)) {
            return 'Tardy';
        } else {
            return 'Late';
        }
    }

    /**
     * Get current active period
     */
    public function getCurrentPeriod($time = null)
    {
        $time = $time ? Carbon::parse($time)->format('H:i:s') : Carbon::now()->format('H:i:s');
        
        if ($this->isTimeInRange($time, $this->am_time_in_start, $this->am_time_in_end)) {
            return 'am_in';
        } elseif ($this->isTimeInRange($time, $this->am_time_out_start, $this->am_time_out_end)) {
            return 'am_out';
        } elseif ($this->isTimeInRange($time, $this->pm_time_in_start, $this->pm_time_in_end)) {
            return 'pm_in';
        } elseif ($this->isTimeInRange($time, $this->pm_time_out_start, $this->pm_time_out_end)) {
            return 'pm_out';
        }
        
        return null;
    }

    /**
     * Helper method to check if time is within range
     */
    private function isTimeInRange($time, $start, $end)
    {
        if (!$start || !$end) {
            return false;
        }
        
        $checkTime = Carbon::createFromFormat('H:i:s', $time);
        $startTime = Carbon::createFromFormat('H:i:s', $start);
        $endTime = Carbon::createFromFormat('H:i:s', $end);
        
        return $checkTime->between($startTime, $endTime);
    }

    /**
     * Validate time ranges for overlaps
     */
    public function validateTimeRanges()
    {
        $timeRanges = [];
        
        if ($this->am_time_in_start && $this->am_time_in_end) {
            $timeRanges[] = [
                'name' => 'AM Time In',
                'start' => $this->am_time_in_start,
                'end' => $this->am_time_in_end
            ];
        }
        
        if ($this->am_time_out_start && $this->am_time_out_end) {
            $timeRanges[] = [
                'name' => 'AM Time Out',
                'start' => $this->am_time_out_start,
                'end' => $this->am_time_out_end
            ];
        }
        
        if ($this->pm_time_in_start && $this->pm_time_in_end) {
            $timeRanges[] = [
                'name' => 'PM Time In',
                'start' => $this->pm_time_in_start,
                'end' => $this->pm_time_in_end
            ];
        }
        
        if ($this->pm_time_out_start && $this->pm_time_out_end) {
            $timeRanges[] = [
                'name' => 'PM Time Out',
                'start' => $this->pm_time_out_start,
                'end' => $this->pm_time_out_end
            ];
        }

        // Check for overlaps
        for ($i = 0; $i < count($timeRanges); $i++) {
            for ($j = $i + 1; $j < count($timeRanges); $j++) {
                $range1 = $timeRanges[$i];
                $range2 = $timeRanges[$j];
                
                // Check if ranges overlap
                if (($range1['start'] < $range2['end'] && $range1['end'] > $range2['start'])) {
                    return [
                        'valid' => false,
                        'message' => "Time ranges '{$range1['name']}' and '{$range2['name']}' overlap. Please adjust the times."
                    ];
                }
            }
        }

        // Check if start time is before end time for each range
        foreach ($timeRanges as $range) {
            if ($range['start'] >= $range['end']) {
                return [
                    'valid' => false,
                    'message' => "In '{$range['name']}' period, start time must be before end time."
                ];
            }
        }

        return ['valid' => true, 'message' => 'All time ranges are valid.'];
    }

    /**
     * Get student count by gender
     */
    public function getStudentCountByGender()
    {
        $students = $this->students;
        return [
            'male' => $students->where('gender', 'male')->count(),
            'female' => $students->where('gender', 'female')->count(),
            'total' => $students->count()
        ];
    }
}
