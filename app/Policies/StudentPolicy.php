<?php

namespace App\Policies;

use App\Models\Student;

class StudentPolicy
{
    public function view($user, Student $student): bool
    {
        if (($user->role ?? null) === 'admin') {
            return true;
        }

        if (($user->role ?? null) === 'teacher') {
            return (int) $student->user_id === (int) $user->id;
        }

        return isset($user->id) && (int) $user->id === (int) $student->id;
    }
}
