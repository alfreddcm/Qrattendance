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
     * Scope to get the current semester based on the current date
     */
    public function scopeCurrent($query, $date = null)
    {
        $currentDate = $date ?: Carbon::now()->toDateString();
        
        return $query->whereDate('start_date', '<=', $currentDate)
                    ->whereDate('end_date', '>=', $currentDate);
    }

 
    public static function getCurrentSemester($date = null)
    {
        $currentDate = $date ?: Carbon::now()->toDateString();
        
         $semester = static::current($currentDate)->first();
        
         if (!$semester) {
            $semester = static::where('status', 'active')->first();
        }
        
         if (!$semester) {
            $semester = static::latest()->first();
        }
        
        return $semester;
    }


}
