<?php

namespace App\Policies;

use App\Enums\AppPermission;
use App\Models\Server;
use App\Models\User;
use App\Services\Permissions\ServerPermissionContext;
use Throwable;

class ServerInvitePolicy
{
    public function __construct() {}

    /**
     * @throws Throwable
     */
    public function store(User $user, Server $server): bool
    {
        $ctx = ServerPermissionContext::for($user, $server);

        return $ctx->resolveServer()->can(AppPermission::CREATE_INVITES);
    }
}
