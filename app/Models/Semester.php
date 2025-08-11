<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Semester extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'school_id',
        'weekdays',
        'am_time_in_start',
        'am_time_in_end',
        'pm_time_out_start',
        'pm_time_out_end',
        'am_time_in_start_input',
        'am_time_in_end_input',
        'pm_time_out_start_input',
        'pm_time_out_end_input',
        'am_time_in_start_display',
        'am_time_in_end_display',
        'pm_time_out_start_display',
        'pm_time_out_end_display',
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

 

     public function getAmTimeInStartInputAttribute()
    {
        return $this->am_time_in_start ? Carbon::createFromFormat('H:i:s', $this->am_time_in_start)->format('H:i') : '07:00';
    }

    public function getAmTimeInEndInputAttribute()
    {
        return $this->am_time_in_end ? Carbon::createFromFormat('H:i:s', $this->am_time_in_end)->format('H:i') : '07:30';
    }

    public function getPmTimeOutStartInputAttribute()
    {
        return $this->pm_time_out_start ? Carbon::createFromFormat('H:i:s', $this->pm_time_out_start)->format('H:i') : '16:30';
    }

    public function getPmTimeOutEndInputAttribute()
    {
        return $this->pm_time_out_end ? Carbon::createFromFormat('H:i:s', $this->pm_time_out_end)->format('H:i') : '17:00';
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
}
