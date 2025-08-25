<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Semester extends Model
{
    protected $fillable = [
        'id',
        'name',
        'school_id',
        'start_date',
        'end_date',
        'morning_period_start',
        'morning_period_end',
        'afternoon_period_start',
        'afternoon_period_end',
        'status',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];


     public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

     public function students()
    {
        return $this->hasMany(Student::class, 'semester_id');
    }

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class, 'semester_id');
    }

    /**
     * Get formatted time inputs for forms
     */
    public function getMorningPeriodStartInputAttribute()
    {
        return $this->morning_period_start ? Carbon::createFromFormat('H:i:s', $this->morning_period_start)->format('H:i') : '';
    }

    public function getMorningPeriodEndInputAttribute()
    {
        return $this->morning_period_end ? Carbon::createFromFormat('H:i:s', $this->morning_period_end)->format('H:i') : '';
    }

    public function getAfternoonPeriodStartInputAttribute()
    {
        return $this->afternoon_period_start ? Carbon::createFromFormat('H:i:s', $this->afternoon_period_start)->format('H:i') : '';
    }

    public function getAfternoonPeriodEndInputAttribute()
    {
        return $this->afternoon_period_end ? Carbon::createFromFormat('H:i:s', $this->afternoon_period_end)->format('H:i') : '';
    }



     public function isWithinPeriod($periodStart, $periodEnd)
    {
        if (!$periodStart || !$periodEnd) return false;
        
        $now = Carbon::now();
        $start = Carbon::createFromFormat('H:i:s', $periodStart);
        $end = Carbon::createFromFormat('H:i:s', $periodEnd);
        
        return $now->between($start, $end);
    }

     public function getCurrentActivePeriod()
    {
        $periods = [
            'AM Time In' => ['start' => $this->am_time_in_start, 'end' => $this->am_time_in_end],
            'AM Time Out' => ['start' => $this->am_time_out_start, 'end' => $this->am_time_out_end],
            'PM Time In' => ['start' => $this->pm_time_in_start, 'end' => $this->pm_time_in_end],
            'PM Time Out' => ['start' => $this->pm_time_out_start, 'end' => $this->pm_time_out_end],
        ];

        foreach ($periods as $name => $times) {
            if ($this->isWithinPeriod($times['start'], $times['end'])) {
                return [
                    'name' => $name,
                    'start' => $times['start'],
                    'end' => $times['end'],
                    'start_formatted' => $times['start'],
                    'end_formatted' => $times['end'],
                ];
            }
        }

        return null;
    }

    /**
     * Determine attendance status based on current time and configured periods
     */
    public function getAttendanceStatus($currentTime = null)
    {
        $now = $currentTime ? Carbon::parse($currentTime) : Carbon::now();
        $timeString = $now->format('H:i:s');

        // Define all periods with their statuses
        $periods = [
            'am_time_in' => [
                'start' => $this->am_time_in_start,
                'end' => $this->am_time_in_end,
                'status' => 'present',
                'period_name' => 'AM Time In'
            ],
            'am_time_out' => [
                'start' => $this->am_time_out_start,
                'end' => $this->am_time_out_end,
                'status' => 'present',
                'period_name' => 'AM Time Out'
            ],
            'pm_time_in' => [
                'start' => $this->pm_time_in_start,
                'end' => $this->pm_time_in_end,
                'status' => 'present',
                'period_name' => 'PM Time In'
            ],
            'pm_time_out' => [
                'start' => $this->pm_time_out_start,
                'end' => $this->pm_time_out_end,
                'status' => 'present',
                'period_name' => 'PM Time Out'
            ]
        ];

        foreach ($periods as $period => $config) {
            if ($config['start'] && $config['end']) {
                if ($timeString >= $config['start'] && $timeString <= $config['end']) {
                    return [
                        'status' => $config['status'],
                        'period' => $period,
                        'period_name' => $config['period_name'],
                        'start_time' => $config['start'],
                        'end_time' => $config['end']
                    ];
                }
            }
        }

        return [
            'status' => 'outside_hours',
            'period' => null,
            'period_name' => 'Outside Operating Hours',
            'start_time' => null,
            'end_time' => null
        ];
    }

    /**
     * Validate time ranges to prevent overlapping
     */
    public function validateTimeRanges()
    {
        $timeRanges = [];
        
        // Collect all time ranges
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
}
