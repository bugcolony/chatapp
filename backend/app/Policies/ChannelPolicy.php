<?php

namespace App\Policies;

use App\Enums\AppPermission;
use App\Enums\ChannelType;
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
        if ($channel->type->isDirect()) {
            return false;
        }

        return $this->canManageChannels($user, $channel->server);
    }

    /**
     * @throws Throwable
     */
    public function destroy(User $user, Channel $channel): bool
    {
        if ($channel->type->isDirect()) {
            return false;
        }

        return $this->canManageChannels($user, $channel->server);
    }

    public function addParticipant(User $user, Channel $channel): bool
    {
        return $channel->type === ChannelType::GROUP_DM
            && $channel->participants()->where('user_id', $user->id)->exists();
    }

    public function removeParticipant(User $user, Channel $channel): bool
    {
        return $channel->type === ChannelType::GROUP_DM
            && $channel->owner_id === $user->id;
    }

    public function leave(User $user, Channel $channel): bool
    {
        return $channel->type === ChannelType::GROUP_DM
            && $channel->participants()->where('user_id', $user->id)->exists();
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
