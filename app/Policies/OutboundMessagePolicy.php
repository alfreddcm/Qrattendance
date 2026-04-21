<?php

namespace App\Policies;

use App\Models\OutboundMessage;

class OutboundMessagePolicy
{
    public function view($user, OutboundMessage $outboundMessage): bool
    {
        if (($user->role ?? null) === 'admin') {
            return (int) $outboundMessage->admin_id === (int) $user->id || $outboundMessage->admin_id === null;
        }

        return ($user->role ?? null) === 'teacher' && (int) $outboundMessage->teacher_id === (int) $user->id;
    }
}
