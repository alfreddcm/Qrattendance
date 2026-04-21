<?php

namespace App\Policies;

use App\Models\AttendanceCode;

class AttendanceCodePolicy
{
    public function view($user, AttendanceCode $attendanceCode): bool
    {
        if (($user->role ?? null) === 'admin') {
            return true;
        }

        return ($user->role ?? null) === 'teacher' && (int) $attendanceCode->teacher_id === (int) $user->id;
    }
}
