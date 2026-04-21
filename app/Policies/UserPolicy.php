<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view($user, User $model): bool
    {
        if (($user->role ?? null) === 'admin') {
            return true;
        }

        return (int) $user->id === (int) $model->id;
    }
}
