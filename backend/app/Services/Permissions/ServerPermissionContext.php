<?php

namespace App\Services\Permissions;

use App\Enums\AppPermission;
use App\Models\Channel;
use App\Models\ChannelPermissionOverride;
use App\Models\Server;
use App\Models\ServerRole;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use Throwable;

final class ServerPermissionContext
{
    private Collection $channelOverrides;

    private Collection $serverRoles;

    private Collection $baseRoleOverrides;

    /**
     * @throws Throwable
     */
    private function __construct(private readonly User $user, private readonly Server $server) {}

    private function isOwner(): bool
    {
        return $this->server->user_id === $this->user->id;
    }

    private function ownerPermissions(): AppPermissions
    {
        return new AppPermissions(AppPermission::all());
    }

    private function ownerChannelPermissions(): AppPermissions
    {
        return $this->ownerPermissions()->mask(AppPermission::allChannelPermissions());
    }

    private function loadServerDependencies(): void
    {
        if (! isset($this->baseRoleOverrides)) {
            $this->baseRoleOverrides = ChannelPermissionOverride::query()
                ->where('server_role_id', $this->server->base_role_id)->get();
        }

        if (! isset($this->serverRoles)) {
            $this->serverRoles = $this->user->serverRoles()
                ->wherePivot('server_id', $this->server->id)
                ->get();
        }
    }

    private function loadChannelDependencies(array $channelIds): void
    {
        if (isset($this->channelOverrides) && collect($channelIds)->diff($this->channelOverrides->keys())->isEmpty()) {
            return;
        }

        $this->channelOverrides = $this->server->channels()
            ->whereIn('id', $channelIds)
            ->with([
                'overrides' => fn ($query) => $query
                    ->where(fn ($query) => $query
                        ->where('user_id', $this->user->id)
                        ->orWhereIn('server_role_id',
                            $this->serverRoles->pluck('id')
                        )
                    ),
            ])
            ->get()
            ->keyBy('id');
    }

    private function buildChannelPermissions(AppPermissions $base, Channel $channel): AppPermissions
    {
        $baseOverrides = $this->baseRoleOverrides->firstWhere('channel_id', $channel->id);

        if ($baseOverrides !== null) {
            $base
                ->discard($baseOverrides->deny)
                ->merge($baseOverrides->allow);
        }

        [$roleOverrides, $userOverrides] = $channel->overrides->partition(
            fn (ChannelPermissionOverride $or) => is_null($or->user_id)
        );

        if ($roleOverrides->isNotEmpty()) {
            $this->applyOverrides($base, $roleOverrides);
        }

        if ($userOverrides->isNotEmpty()) {
            $this->applyOverrides($base, $userOverrides);
        }

        if (! $base->can(AppPermission::VIEW_CHANNELS)) {
            $base = new AppPermissions;
        }

        return $base;
    }

    private function applyOverrides(AppPermissions $base, Collection $overrides): void
    {
        ['allow' => $allow, 'deny' => $deny] = $this->consolidateOverrides($overrides);

        $base
            ->discard($deny->value())
            ->merge($allow->value());
    }

    private function consolidateOverrides(Collection $overrides): array
    {
        return $overrides->reduce(function ($carry, ChannelPermissionOverride $override) {
            $carry['allow']->merge($override->allow);
            $carry['deny']->merge($override->deny);

            return $carry;
        }, [
            'allow' => new AppPermissions,
            'deny' => new AppPermissions,
        ]);
    }

    private function validMembership(): bool
    {
        return ! is_null($this->user->memberships()->active()->where('server_id', $this->server->id)->first());
    }

    public function resolveServer(): AppPermissions
    {
        if ($this->isOwner()) {
            return $this->ownerPermissions();
        }

        $base = new AppPermissions($this->server->baseRole->permissions);

        return $this->serverRoles->reduce(function (AppPermissions $carry, ServerRole $role) {
            return $carry->merge($role->permissions ?? 0);
        }, $base);
    }

    public function resolveChannels(Collection $channels): \Illuminate\Support\Collection
    {
        $permissions = collect();

        if (! $channels->every(fn (Channel $channel) => $channel->server_id === $this->server->id)) {
            throw new RuntimeException('Channels ownership mismatch');
        }

        if ($this->isOwner()) {
            return $channels->mapWithKeys(fn (Channel $channel) => [
                $channel->id => $this->ownerChannelPermissions(),
            ]);
        }

        $this->loadChannelDependencies($channels->pluck('id')->all());

        $base = $this->resolveServer();

        $channels->each(function (Channel $ch) use ($base, $permissions) {
            $permissionBase = clone $base;

            /** @var Channel|null $channelData */
            $channelData = $this->channelOverrides->get($ch->id);

            if ($channelData === null) {
                throw new RuntimeException('Channel data failed to load');
            }

            $channelPermissions = $this->buildChannelPermissions($permissionBase, $channelData);

            $permissions->put($ch->id, $channelPermissions->mask(AppPermission::allChannelPermissions()));
        });

        return $permissions;
    }

    /**
     * @throws Exception
     */
    public function resolveChannel(Channel $channel): AppPermissions
    {
        if ($channel->server_id !== $this->server->id) {
            throw new RuntimeException('Channel does not belong to this server');
        }

        if ($this->isOwner()) {
            return $this->ownerChannelPermissions();
        }

        $this->loadChannelDependencies([$channel->id]);

        $base = $this->resolveServer();

        /** @var Channel|null $channelData */
        $channelData = $this->channelOverrides->get($channel->id);

        if ($channelData === null) {
            throw new RuntimeException('Channel data failed to load');
        }

        return $this->buildChannelPermissions($base, $channelData)->mask(AppPermission::allChannelPermissions());
    }

    /**
     * @throws Throwable
     */
    public static function for(User $user, Server $server): self
    {
        $context = new self($user, $server);

        throw_unless($context->validMembership(), new Exception('Invalid server membership'));

        $context->loadServerDependencies();

        return $context;
    }
}
