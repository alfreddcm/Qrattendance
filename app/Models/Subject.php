<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'teacher_id',
        'semester_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the teacher that owns the subject.
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the semester that owns the subject.
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Get the sections that are assigned to this subject.
     */
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'subject_sections')
                    ->withPivot('schedule_day', 'start_time', 'end_time', 'room')
                    ->withTimestamps();
    }

    /**
     * Get the attendance records for this subject.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
