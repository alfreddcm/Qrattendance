<?php

namespace App\Policies;

use App\Models\AttendanceSession;

class AttendanceSessionPolicy
{
    public function view($user, AttendanceSession $attendanceSession): bool
    {
        if (($user->role ?? null) === 'admin') {
            return true;
        }

        return ($user->role ?? null) === 'teacher' && (int) $attendanceSession->teacher_id === (int) $user->id;
    }
}
