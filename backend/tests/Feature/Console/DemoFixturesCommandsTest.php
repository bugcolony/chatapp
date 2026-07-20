<?php

use App\Enums\ChannelType;
use App\Enums\SystemRole;
use App\Models\Channel;
use App\Models\ChannelPermissionOverride;
use App\Models\Member;
use App\Models\Message;
use App\Models\Server;
use App\Models\ServerInvite;
use App\Models\ServerRole;
use App\Models\User;
use App\Services\DemoFixtureManager;
use Database\Seeders\DemoFixturesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function demoFixtureEmails(): array
{
    return array_column(DemoFixtureManager::USERS, 'email');
}

function demoFixtureServerNames(): array
{
    return array_column(DemoFixtureManager::SERVERS, 'name');
}

function demoFixtureChannelCount(): int
{
    return array_sum(array_map(
        static fn (array $server): int => count($server['channels']),
        DemoFixtureManager::SERVERS,
    ));
}

function demoFixtureCategoryCount(): int
{
    return array_sum(array_map(
        static fn (array $server): int => count(array_filter(
            $server['channels'],
            static fn (array $channel): bool => $channel['type'] === ChannelType::Category->value,
        )),
        DemoFixtureManager::SERVERS,
    ));
}

test('demo fixtures can be provisioned repeatedly', function () {
    $this->seed(DemoFixturesSeeder::class);

    $users = User::query()
        ->whereIn('email', demoFixtureEmails())
        ->orderBy('id')
        ->get();
    $owner = $users->firstWhere('email', DemoFixtureManager::OWNER_EMAIL);
    $servers = Server::query()
        ->whereIn('name', demoFixtureServerNames())
        ->orderBy('id')
        ->get();
    $userIds = $users->pluck('id')->sort()->values()->all();

    expect($users)->toHaveCount(10)
        ->and($servers)->toHaveCount(3)
        ->and(Member::query()->whereNull('left_at')->count())->toBe(30)
        ->and(Channel::query()->count())->toBe(demoFixtureChannelCount())
        ->and(Channel::query()->where('type', ChannelType::Category)->count())->toBe(demoFixtureCategoryCount())
        ->and(Channel::query()->whereNotNull('parent_id')->count())
        ->toBe(demoFixtureChannelCount() - demoFixtureCategoryCount())
        ->and(Message::query()->count())->toBe(30);

    foreach ($users as $user) {
        expect(Hash::check(DemoFixtureManager::PASSWORD, $user->password))->toBeTrue()
            ->and($user->hasExactRoles(SystemRole::User->value))->toBeTrue()
            ->and($user->memberships()->whereNull('left_at')->count())->toBe(3);
    }

    foreach ($servers as $server) {
        expect($server->user_id)->toBe($owner->id)
            ->and($server->members()->whereNull('left_at')->pluck('user_id')->sort()->values()->all())
            ->toBe($userIds);

        $serverSpec = collect(DemoFixtureManager::SERVERS)->firstWhere('name', $server->name);
        $channels = $server->channels()->get()->keyBy('name');

        foreach ($serverSpec['channels'] as $channelSpec) {
            $channel = $channels[$channelSpec['name']];
            $expectedParentId = isset($channelSpec['parent'])
                ? $channels[$channelSpec['parent']]->id
                : null;

            expect($channel->type)->toBe(ChannelType::from($channelSpec['type']))
                ->and($channel->parent_id)->toBe($expectedParentId);
        }
    }

    $ids = [
        'users' => $users->pluck('id', 'email')->all(),
        'servers' => $servers->pluck('id', 'name')->all(),
        'channels' => Channel::query()->orderBy('id')->pluck('id')->all(),
        'messages' => Message::query()->orderBy('id')->pluck('id')->all(),
    ];

    $this->artisan('demo:provision')->assertSuccessful();

    expect(User::query()->whereIn('email', demoFixtureEmails())->orderBy('id')->pluck('id', 'email')->all())
        ->toBe($ids['users'])
        ->and(Server::query()->whereIn('name', demoFixtureServerNames())->orderBy('id')->pluck('id', 'name')->all())
        ->toBe($ids['servers'])
        ->and(Channel::query()->orderBy('id')->pluck('id')->all())->toBe($ids['channels'])
        ->and(Message::query()->orderBy('id')->pluck('id')->all())->toBe($ids['messages'])
        ->and(Member::query()->count())->toBe(30);
});

test('demo fixtures can be reset to their canonical state', function () {
    $this->artisan('demo:provision')->assertSuccessful();

    $unrelatedUser = User::factory()->create();
    $unrelatedServer = Server::factory()->for($unrelatedUser, 'owner')->create();
    $owner = User::query()->where('email', DemoFixtureManager::OWNER_EMAIL)->firstOrFail();
    $secondUser = User::query()->where('email', 'example2@example.com')->firstOrFail();
    $gamingServer = Server::query()->where('name', 'Gaming Lounge')->firstOrFail();
    $deletedChannel = Channel::query()
        ->where('server_id', $gamingServer->id)
        ->where('name', 'lfg')
        ->firstOrFail();
    $deletedMessage = Message::query()->where('server_id', $gamingServer->id)->firstOrFail();

    $owner->update(['name' => 'Changed Owner', 'password' => 'changed-password']);
    Member::query()
        ->where('server_id', $gamingServer->id)
        ->where('user_id', $secondUser->id)
        ->update(['left_at' => now()]);
    $gamingServer->update(['name' => 'Renamed Gaming Lounge']);
    $deletedChannel->delete();
    $deletedMessage->delete();
    Server::query()->create(['user_id' => $secondUser->id, 'name' => 'Temporary Demo Server']);

    $this->artisan('demo:reset')->assertSuccessful();

    $users = User::query()->whereIn('email', demoFixtureEmails())->get();
    $restoredOwner = $users->firstWhere('email', DemoFixtureManager::OWNER_EMAIL);
    $servers = Server::query()->whereIn('name', demoFixtureServerNames())->get();

    expect($users)->toHaveCount(10)
        ->and($restoredOwner->name)->toBe('Example User 1')
        ->and(Hash::check(DemoFixtureManager::PASSWORD, $restoredOwner->password))->toBeTrue()
        ->and($servers)->toHaveCount(3)
        ->and($servers->every(fn (Server $server): bool => $server->user_id === $restoredOwner->id))->toBeTrue()
        ->and(Member::query()->whereIn('server_id', $servers->pluck('id'))->whereNull('left_at')->count())->toBe(30)
        ->and(Channel::withTrashed()->whereIn('server_id', $servers->pluck('id'))->count())
        ->toBe(demoFixtureChannelCount())
        ->and(Message::withTrashed()->whereIn('server_id', $servers->pluck('id'))->count())->toBe(30)
        ->and(Server::query()->where('name', 'Renamed Gaming Lounge')->exists())->toBeFalse()
        ->and(Server::query()->where('name', 'Temporary Demo Server')->exists())->toBeFalse()
        ->and($unrelatedUser->fresh())->not->toBeNull()
        ->and($unrelatedServer->fresh())->not->toBeNull();
});

test('demo fixtures and demo user activity can be removed', function () {
    $this->artisan('demo:provision')->assertSuccessful();

    $demoUser = User::query()->where('email', 'example2@example.com')->firstOrFail();
    $demoUserIds = User::query()->whereIn('email', demoFixtureEmails())->pluck('id')->all();
    $demoServerIds = Server::query()->whereIn('user_id', $demoUserIds)->pluck('id')->all();
    $unrelatedUser = User::factory()->create();
    $unrelatedServer = Server::factory()->for($unrelatedUser, 'owner')->create();
    $unrelatedRole = ServerRole::query()->create([
        'server_id' => $unrelatedServer->id,
        'name' => ServerRole::BASE_ROLE_NAME,
        'permissions' => 0,
        'is_system' => true,
    ]);
    $unrelatedServer->update(['base_role_id' => $unrelatedRole->id]);
    $unrelatedChannel = Channel::factory()->for($unrelatedServer)->create();
    $membership = Member::factory()->for($demoUser)->for($unrelatedServer)->create();
    $message = Message::factory()->inChannel($unrelatedChannel)->from($demoUser)->create();
    $invite = ServerInvite::query()->create([
        'server_id' => $unrelatedServer->id,
        'channel_id' => $unrelatedChannel->id,
        'created_by' => $demoUser->id,
        'code' => 'outside-demo',
    ]);
    $override = ChannelPermissionOverride::query()->create([
        'channel_id' => $unrelatedChannel->id,
        'user_id' => $demoUser->id,
        'allow' => 0,
        'deny' => 0,
    ]);

    DB::table('server_role_user')->insert([
        'server_id' => $unrelatedServer->id,
        'user_id' => $demoUser->id,
        'server_role_id' => $unrelatedRole->id,
    ]);
    DB::table('password_reset_tokens')->insert([
        'email' => $demoUser->email,
        'token' => 'demo-reset-token',
        'created_at' => now(),
    ]);
    $tokenId = $demoUser->createToken('demo-token')->accessToken->id;

    $this->artisan('demo:remove')->assertSuccessful();
    $this->artisan('demo:remove')->assertSuccessful();

    expect(User::query()->whereIn('email', demoFixtureEmails())->count())->toBe(0)
        ->and(Server::query()->whereIn('id', $demoServerIds)->count())->toBe(0)
        ->and(Channel::withTrashed()->whereIn('server_id', $demoServerIds)->count())->toBe(0)
        ->and(Message::withTrashed()->whereIn('server_id', $demoServerIds)->count())->toBe(0)
        ->and($unrelatedUser->fresh())->not->toBeNull()
        ->and($unrelatedServer->fresh())->not->toBeNull()
        ->and($unrelatedChannel->fresh())->not->toBeNull()
        ->and(Member::query()->whereKey($membership->id)->exists())->toBeFalse()
        ->and(Message::withTrashed()->whereKey($message->id)->exists())->toBeFalse()
        ->and(ServerInvite::query()->whereKey($invite->id)->exists())->toBeFalse()
        ->and(ChannelPermissionOverride::query()->whereKey($override->id)->exists())->toBeFalse()
        ->and(DB::table('personal_access_tokens')->where('id', $tokenId)->exists())->toBeFalse()
        ->and(DB::table('password_reset_tokens')->where('email', $demoUser->email)->exists())->toBeFalse()
        ->and(DB::table('server_role_user')->where('user_id', $demoUser->id)->exists())->toBeFalse()
        ->and(DB::table('model_has_roles')->whereIn('model_id', $demoUserIds)->exists())->toBeFalse();
});
