<?php

namespace App\Policies;

use App\Models\School;

class SchoolPolicy
{
    public function view($user, School $school): bool
    {
        return ($user->role ?? null) === 'admin';
    }
}
