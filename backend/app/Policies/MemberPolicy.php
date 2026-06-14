<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class MemberPolicy
{
    public function __construct() {}

    public function destroy(User $user, Server $server): bool
    {
        return $server->user_id !== $user->id;
    }
}
