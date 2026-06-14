<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    public function view(User $user, Server $server): bool
    {
        return $user->memberships()->active()->where('server_id', $server->id)->exists();
    }
}
