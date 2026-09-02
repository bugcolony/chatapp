<?php

use App\Actions\User\DeleteAccount;
use App\Jobs\DeleteFileObjects;
use App\Models\Channel;
use App\Models\ChannelPermissionOverride;
use App\Models\File;
use App\Models\Member;
use App\Models\Server;
use App\Models\ServerRole;
use App\Models\SocialAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function closableUser(): User
{
    Storage::fake('local');
    config()->set('filesystems.default', 'local');

    return User::factory()->create([
        'name' => 'Ada Lovelace',
        'username' => 'ada',
        'email' => 'ada@example.com',
    ]);
}

it('scrubs identity off the user row but keeps the row', function () {
    $user = closableUser();

    app(DeleteAccount::class)->execute($user);

    $user->refresh();

    expect($user->exists)->toBeTrue()
        ->and($user->name)->toBe('Deleted User')
        ->and($user->username)->toBe("deleted-{$user->id}")
        ->and($user->email)->toBe("deleted-{$user->id}@deleted.invalid")
        ->and($user->password)->toBeNull()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->isClosed())->toBeTrue();
});

it('scrubs the denormalised nickname in every server', function () {
    $user = closableUser();
    $server = Server::factory()->create();

    Member::factory()->create([
        'user_id' => $user->id,
        'server_id' => $server->id,
        'nickname' => 'Ada',
    ]);

    app(DeleteAccount::class)->execute($user);

    expect(Member::where('user_id', $user->id)->value('nickname'))->toBe('Deleted User');
});

it('leaves every active server without rewriting earlier departures', function () {
    $user = closableUser();
    $earlier = now()->subMonth();

    $active = Member::factory()->create(['user_id' => $user->id, 'left_at' => null]);
    $left = Member::factory()->create(['user_id' => $user->id, 'left_at' => $earlier]);

    app(DeleteAccount::class)->execute($user);

    expect($active->refresh()->left_at)->not->toBeNull()
        ->and(Carbon::parse($left->refresh()->left_at)->timestamp)->toBe($earlier->timestamp);
});

it('deletes credentials, roles and per-user overrides', function () {
    $user = closableUser();
    $server = Server::factory()->create();
    $role = ServerRole::factory()->create(['server_id' => $server->id]);
    $channel = Channel::factory()->create(['server_id' => $server->id]);

    SocialAccount::factory()->create(['user_id' => $user->id]);
    $user->createToken('test');
    $user->serverRoles()->attach($role->id, ['server_id' => $server->id]);

    ChannelPermissionOverride::create([
        'channel_id' => $channel->id,
        'user_id' => $user->id,
        'allow' => 1,
        'deny' => 0,
    ]);

    app(DeleteAccount::class)->execute($user);

    expect(SocialAccount::where('user_id', $user->id)->count())->toBe(0)
        ->and($user->tokens()->count())->toBe(0)
        ->and($user->serverRoles()->count())->toBe(0)
        ->and(ChannelPermissionOverride::where('user_id', $user->id)->count())->toBe(0);
});

it('drops the avatar and queues object cleanup', function () {
    Bus::fake();

    $user = closableUser();

    Storage::disk('local')->put('avatars/ada.png', 'x');

    $file = File::create([
        'disk' => 'local',
        'source_path' => 'avatars/ada.png',
        'original_name' => 'ada.png',
        'mime_type' => 'image/png',
        'size' => 1,
    ]);

    $user->forceFill(['avatar_file_id' => $file->id])->save();

    app(DeleteAccount::class)->execute($user);

    expect($user->refresh()->avatar_file_id)->toBeNull()
        ->and(File::find($file->id))->toBeNull();

    Bus::assertDispatched(DeleteFileObjects::class);
});

it('keeps owned servers pointing at the tombstone row', function () {
    $user = closableUser();
    $server = Server::factory()->create(['user_id' => $user->id]);

    app(DeleteAccount::class)->execute($user);

    expect($server->refresh()->user_id)->toBe($user->id);
});
