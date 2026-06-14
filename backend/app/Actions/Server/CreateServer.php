<?php

namespace App\Actions\Server;

use App\Data\Server\CreateServerData;
use App\Enums\AppPermission;
use App\Models\Server;
use App\Models\ServerRole;
use App\Models\User;

class CreateServer
{
    public function execute(User $actor, CreateServerData $data): Server
    {
        $server = $actor->ownedServers()->create($data->toArray());

        $server->members()->create([
            'user_id' => $actor->id,
        ]);

        $server->channels()->create([
            'name' => 'general',
        ]);

        $baseRole = $server->roles()->create([
            'name' => ServerRole::BASE_ROLE_NAME,
            'is_system' => true,
            'permissions' => AppPermission::basePermissions(),
        ]);

        $server->baseRole()->associate($baseRole)->save();

        return $server;
    }
}
