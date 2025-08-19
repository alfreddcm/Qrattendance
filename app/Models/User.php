<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'is_active',
        'phone_number',
        'position',
        'school_id',
        'section_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Role-based helper methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    // Scope for filtering by role
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeTeachers($query)
    {
        return $query->where('role', 'teacher');
    }

    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Relationship: User belongs to a School
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    // Relationship: User belongs to a Section (single section - legacy)
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    // Relationship: User belongs to many Sections (many-to-many via pivot)
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_teacher', 'teacher_id', 'section_id')
                    ->withTimestamps();
    }

    // Accessor for section_name (from section relationship)
    public function getSectionNameAttribute()
    {
        return $this->section ? $this->section->name : null;
    }

    // Relationship: User has many Students
    public function students()
    {
        return $this->hasMany(Student::class, 'user_id');
    }
}
