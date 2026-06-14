<?php

use App\Enums\AppPermission;
use App\Models\Channel;
use App\Models\ChannelPermissionOverride;
use App\Models\Member;
use App\Models\Server;
use App\Models\ServerRole;
use App\Models\User;
use App\Services\Permissions\ServerPermissionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

function permissionMask(AppPermission ...$permissions): int
{
    return array_reduce(
        $permissions,
        fn (int $mask, AppPermission $permission): int => $mask | $permission->value,
        0,
    );
}

/**
 * @return array{
 *     owner: User,
 *     member: User,
 *     server: Server,
 *     baseRole: ServerRole,
 *     channels: Collection<int, Channel>
 * }
 */
function permissionFixture(int $basePermissions = 0, int $channelCount = 2): array
{
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $owner->id]);

    $baseRole = ServerRole::factory()->system()->create([
        'server_id' => $server->id,
        'permissions' => $basePermissions,
    ]);

    $server->update(['base_role_id' => $baseRole->id]);

    Member::factory()->create([
        'user_id' => $owner->id,
        'server_id' => $server->id,
    ]);

    Member::factory()->create([
        'user_id' => $member->id,
        'server_id' => $server->id,
    ]);

    $channels = Channel::factory()
        ->count($channelCount)
        ->create(['server_id' => $server->id]);

    return compact('owner', 'member', 'server', 'baseRole', 'channels');
}

function assignServerRole(User $user, Server $server, int $permissions = 0): ServerRole
{
    $role = ServerRole::factory()->create([
        'server_id' => $server->id,
        'permissions' => $permissions,
    ]);

    $user->serverRoles()->attach($role->id, ['server_id' => $server->id]);

    return $role;
}

function createRoleOverride(
    Channel $channel,
    ServerRole $role,
    int $allow = 0,
    int $deny = 0,
): ChannelPermissionOverride {
    return ChannelPermissionOverride::query()->create([
        'channel_id' => $channel->id,
        'server_role_id' => $role->id,
        'allow' => $allow,
        'deny' => $deny,
    ]);
}

function createUserOverride(
    Channel $channel,
    User $user,
    int $allow = 0,
    int $deny = 0,
): ChannelPermissionOverride {
    return ChannelPermissionOverride::query()->create([
        'channel_id' => $channel->id,
        'user_id' => $user->id,
        'allow' => $allow,
        'deny' => $deny,
    ]);
}

test('server permissions combine the base role and assigned roles', function () {
    $fixture = permissionFixture(permissionMask(
        AppPermission::VIEW_CHANNELS,
        AppPermission::SEND_MESSAGES,
    ));

    assignServerRole(
        $fixture['member'],
        $fixture['server'],
        permissionMask(AppPermission::ATTACH_FILES, AppPermission::MANAGE_CHANNELS),
    );

    $permissions = ServerPermissionContext::for(
        $fixture['member'],
        $fixture['server'],
    )->resolveServer();

    expect($permissions->value())->toBe(permissionMask(
        AppPermission::VIEW_CHANNELS,
        AppPermission::SEND_MESSAGES,
        AppPermission::ATTACH_FILES,
        AppPermission::MANAGE_CHANNELS,
    ));
});

test('the server owner receives every server and channel permission', function () {
    $fixture = permissionFixture();
    $channel = $fixture['channels']->firstOrFail();

    createRoleOverride(
        $channel,
        $fixture['baseRole'],
        deny: AppPermission::VIEW_CHANNELS->value,
    );

    $context = ServerPermissionContext::for($fixture['owner'], $fixture['server']);

    expect($context->resolveServer()->value())->toBe(AppPermission::all())
        ->and($context->resolveChannel($channel)->value())
        ->toBe(AppPermission::allChannelPermissions())
        ->and($context->resolveChannels($fixture['channels'])
            ->map(fn ($permissions): int => $permissions->value()))
        ->each->toBe(AppPermission::allChannelPermissions());
});

test('a base role channel override changes the inherited server permissions', function () {
    $fixture = permissionFixture(permissionMask(
        AppPermission::VIEW_CHANNELS,
        AppPermission::SEND_MESSAGES,
        AppPermission::ADD_REACTIONS,
    ));
    $channel = $fixture['channels']->firstOrFail();

    createRoleOverride(
        $channel,
        $fixture['baseRole'],
        allow: AppPermission::ATTACH_FILES->value,
        deny: AppPermission::SEND_MESSAGES->value,
    );

    $permissions = ServerPermissionContext::for(
        $fixture['member'],
        $fixture['server'],
    )->resolveChannel($channel);

    expect($permissions->value())->toBe(permissionMask(
        AppPermission::VIEW_CHANNELS,
        AppPermission::ADD_REACTIONS,
        AppPermission::ATTACH_FILES,
    ));
});

test('an assigned role allow wins over another assigned role deny', function () {
    $fixture = permissionFixture(permissionMask(
        AppPermission::VIEW_CHANNELS,
        AppPermission::ADD_REACTIONS,
    ));
    $channel = $fixture['channels']->firstOrFail();

    $denyingRole = assignServerRole($fixture['member'], $fixture['server']);
    $allowingRole = assignServerRole($fixture['member'], $fixture['server']);

    createRoleOverride(
        $channel,
        $denyingRole,
        deny: AppPermission::SEND_MESSAGES->value,
    );
    createRoleOverride(
        $channel,
        $allowingRole,
        allow: AppPermission::SEND_MESSAGES->value,
    );

    $permissions = ServerPermissionContext::for(
        $fixture['member'],
        $fixture['server'],
    )->resolveChannel($channel);

    expect($permissions->can(AppPermission::SEND_MESSAGES))->toBeTrue();
});

test('a user override is applied after all assigned role overrides', function () {
    $fixture = permissionFixture(permissionMask(
        AppPermission::VIEW_CHANNELS,
        AppPermission::SEND_MESSAGES,
        AppPermission::ADD_REACTIONS,
    ));
    $channel = $fixture['channels']->firstOrFail();
    $role = assignServerRole($fixture['member'], $fixture['server']);

    createRoleOverride(
        $channel,
        $role,
        allow: AppPermission::ATTACH_FILES->value,
        deny: AppPermission::SEND_MESSAGES->value,
    );
    createUserOverride(
        $channel,
        $fixture['member'],
        allow: AppPermission::SEND_MESSAGES->value,
        deny: AppPermission::ATTACH_FILES->value,
    );

    $permissions = ServerPermissionContext::for(
        $fixture['member'],
        $fixture['server'],
    )->resolveChannel($channel);

    expect($permissions->value())->toBe(permissionMask(
        AppPermission::VIEW_CHANNELS,
        AppPermission::SEND_MESSAGES,
        AppPermission::ADD_REACTIONS,
    ));
});

test('a channel without view permission resolves to no permissions', function () {
    $fixture = permissionFixture(permissionMask(
        AppPermission::VIEW_CHANNELS,
        AppPermission::SEND_MESSAGES,
    ));
    $channel = $fixture['channels']->firstOrFail();

    createUserOverride(
        $channel,
        $fixture['member'],
        allow: AppPermission::SEND_MESSAGES->value,
        deny: AppPermission::VIEW_CHANNELS->value,
    );

    $permissions = ServerPermissionContext::for(
        $fixture['member'],
        $fixture['server'],
    )->resolveChannel($channel);

    expect($permissions->value())->toBe(0);
});

test('single and batch channel resolution return identical isolated masks', function () {
    $fixture = permissionFixture(permissionMask(
        AppPermission::VIEW_CHANNELS,
        AppPermission::SEND_MESSAGES,
        AppPermission::ADD_REACTIONS,
    ));
    [$firstChannel, $secondChannel] = $fixture['channels']->values()->all();
    $role = assignServerRole($fixture['member'], $fixture['server']);

    createRoleOverride(
        $firstChannel,
        $fixture['baseRole'],
        deny: AppPermission::SEND_MESSAGES->value,
    );
    createRoleOverride(
        $firstChannel,
        $role,
        allow: AppPermission::ATTACH_FILES->value,
    );
    createUserOverride(
        $secondChannel,
        $fixture['member'],
        deny: AppPermission::ADD_REACTIONS->value,
    );

    $context = ServerPermissionContext::for($fixture['member'], $fixture['server']);
    $firstSingle = $context->resolveChannel($firstChannel)->value();
    $secondSingle = $context->resolveChannel($secondChannel)->value();
    $batch = $context->resolveChannels($fixture['channels']);

    expect($batch->get($firstChannel->id)->value())->toBe($firstSingle)
        ->and($batch->get($secondChannel->id)->value())->toBe($secondSingle)
        ->and($firstSingle)->toBe(permissionMask(
            AppPermission::VIEW_CHANNELS,
            AppPermission::ADD_REACTIONS,
            AppPermission::ATTACH_FILES,
        ))
        ->and($secondSingle)->toBe(permissionMask(
            AppPermission::VIEW_CHANNELS,
            AppPermission::SEND_MESSAGES,
        ));
});

test('inactive members cannot create a permission context', function () {
    $fixture = permissionFixture(AppPermission::basePermissions());

    $fixture['member']->memberships()
        ->where('server_id', $fixture['server']->id)
        ->update(['left_at' => now()]);

    ServerPermissionContext::for($fixture['member'], $fixture['server']);
})->throws(Exception::class, 'Invalid server membership');

test('channels from another server cannot be resolved', function () {
    $fixture = permissionFixture(AppPermission::basePermissions());
    $otherServer = Server::factory()->create();
    $otherChannel = Channel::factory()->create(['server_id' => $otherServer->id]);
    $context = ServerPermissionContext::for($fixture['member'], $fixture['server']);

    $context->resolveChannel($otherChannel);
})->throws(RuntimeException::class, 'Channel does not belong to this server');
