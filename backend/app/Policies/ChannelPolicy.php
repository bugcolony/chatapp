<?php

namespace App\Policies;

use App\Enums\AppPermission;
use App\Models\Channel;
use App\Models\Server;
use App\Models\User;
use App\Services\Permissions\ServerPermissionContext;
use Throwable;

class ChannelPolicy
{
    /**
     * @throws Throwable
     */
    public function store(User $user, Server $server): bool
    {
        return $this->canManageChannels($user, $server);
    }

    /**
     * @throws Throwable
     */
    public function update(User $user, Channel $channel): bool
    {
        return $this->canManageChannels($user, $channel->server);
    }

    /**
     * @throws Throwable
     */
    public function destroy(User $user, Channel $channel): bool
    {
        return $this->canManageChannels($user, $channel->server);
    }

    /**
     * @throws Throwable
     */
    private function canManageChannels(User $user, Server $server): bool
    {
        $ctx = ServerPermissionContext::for($user, $server);

        return $ctx->resolveServer()->can(AppPermission::MANAGE_CHANNELS);
    }
}
