<?php

namespace App\Models;

use App\Models\Concerns\HasUuidRouteKey;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    use HasUuidRouteKey;

    protected $fillable = [
        'uuid',
        'school_id',
        'school_year_start',
        'school_year_end',
        'name',
        'start_date',
        'end_date',
        'status',
        'description',
    ];

    protected $casts = [
        'school_year_start' => 'integer',
        'school_year_end' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getSchoolYearAttribute()
    {
        return $this->school_year_start . '–' . $this->school_year_end;
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public static function getCurrentSchoolYear($schoolId = null)
    {
        $query = self::where('status', 'active');
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        return $query->first();
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'school_year_id');
    }

    public function sections()
    {
        return $this->hasMany(Section::class, 'school_year_id');
    }

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class, 'school_year_id');
    }
}
