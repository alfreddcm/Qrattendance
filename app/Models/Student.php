<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    // Table name (optional if your table is 'students')
    protected $table = 'students';

    // Fillable fields for mass assignment
    protected $fillable = [
        'id_no', 
        'name', 
        'gender', 
        'age', 
        'address', 
        'cp_no', 
        'picture',
        'contact_person_name',
        'contact_person_relationship',
        'contact_person_contact',
        'semester_id',
        'user_id',
        'school_id',
        'qr_code'
    ];

    // Relationship: Student belongs to a Semester
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    // Relationship: Student belongs to a User (Teacher)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship: Student belongs to a School
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    // Relationship: Student has many Attendances
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Analytics methods
    public function getAttendanceRate($startDate = null, $endDate = null)
    {
        $query = $this->attendances();
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $totalDays = $query->count();
        $presentDays = $query->whereNotNull('time_in_am')->count();
        
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }

    public function getPunctualityRate($startDate = null, $endDate = null, $thresholdTime = '08:00')
    {
        $query = $this->attendances()->whereNotNull('time_in_am');
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $totalAttendances = $query->count();
        $punctualAttendances = $query->where('time_in_am', '<=', $thresholdTime)->count();
        
        return $totalAttendances > 0 ? round(($punctualAttendances / $totalAttendances) * 100, 2) : 0;
    }

    public function getAbsentDays($startDate = null, $endDate = null)
    {
        $query = $this->attendances();
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        return $query->whereNull('time_in_am')->whereNull('time_in_pm')->count();
    }

    public function getAverageHours($startDate = null, $endDate = null)
    {
        $query = $this->attendances();
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $attendances = $query->get();
        $totalHours = $attendances->sum('total_hours');
        
        return $attendances->count() > 0 ? round($totalHours / $attendances->count(), 2) : 0;
    }
}