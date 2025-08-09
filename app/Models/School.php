<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'address',
        'logo',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'school_id', 'id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'school_id', 'id');
    }
}
