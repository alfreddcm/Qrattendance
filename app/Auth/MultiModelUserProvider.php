<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class MultiModelUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        // Try to find in User model first
        $user = parent::retrieveById($identifier);

        if ($user) {
            return $user;
        }

        // If not found, try to find in Student model
        $student = \App\Models\Student::find($identifier);

        return $student;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        // Try User model first
        $user = parent::retrieveByToken($identifier, $token);

        if ($user) {
            return $user;
        }

        // Try Student model
        $student = \App\Models\Student::where('id', $identifier)
            ->where('remember_token', $token)
            ->first();

        return $student;
    }
}
