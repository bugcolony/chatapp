<?php

namespace App\Policies;

use App\Models\User;

class ChannelPolicy
{
    /** TODO: IMPLEMENT */
    public function create(User $user): true
    {
        throw new \Exception('Not implemented');
    }
}
