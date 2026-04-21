<?php

namespace App\Policies;

use App\Models\SchoolYear;

class SchoolYearPolicy
{
    public function view($user, SchoolYear $schoolYear): bool
    {
        if (($user->role ?? null) === 'admin') {
            return true;
        }

        return ($user->role ?? null) === 'teacher' && (int) $user->school_id === (int) $schoolYear->school_id;
    }
}
