<?php

namespace App\Policies;

use App\Enums\AppPermission;
use App\Models\Channel;
use App\Models\User;
use App\Services\Permissions\ServerPermissionContext;
use Throwable;

class MessagePolicy
{
    public function __construct()
    {
        //
    }

    /**
     * @throws Throwable
     */
    public function store(User $user, Channel $channel): bool
    {
        $ctx = ServerPermissionContext::for($user, $channel->server);

        return $ctx->resolveChannel($channel)->can(AppPermission::SEND_MESSAGES);
    }
}
