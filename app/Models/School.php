<?php

namespace App\Models;

use App\Models\Concerns\HasUuidRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory, HasUuidRouteKey;

    protected $fillable = [
        'uuid',
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
