<?php

use App\Actions\Server\CreateServer;
use App\Data\Server\CreateServerData;
use App\Enums\AppPermission;
use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\Member;
use App\Models\Server;
use App\Models\ServerRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('unauthenticated user cannot create a server', function () {
    $response = $this->postJson('/api/v1/servers', ['name' => 'My Server']);

    $response->assertUnauthorized();
    expect(Server::count())->toBe(0);
});

test('authenticated user can create a server', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/servers', ['name' => 'My Server']);

    $response->assertCreated();

    expect(Server::count())->toBe(1);
    $server = Server::first();
    expect($server->name)->toBe('My Server')
        ->and($server->user_id)->toBe($user->id);

    $response->assertJsonPath('data.id', $server->id)
        ->assertJsonPath('data.owner_id', $user->id);
});

test('creating a server also creates a member record for the owner', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/servers', ['name' => 'My Server'])->assertCreated();

    $server = Server::first();
    expect(Member::count())->toBe(1);
    $member = Member::first();
    expect($member->server_id)->toBe($server->id)
        ->and($member->user_id)->toBe($user->id);
});

test('creating a server seeds a default general text channel', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/servers', ['name' => 'My Server'])->assertCreated();

    $server = Server::first();
    expect(Channel::count())->toBe(1);
    $channel = Channel::first();
    expect($channel->server_id)->toBe($server->id)
        ->and($channel->name)->toBe('general')
        ->and($channel->type)->toBe(ChannelType::Text);
});

test('creating a server seeds and associates its base role without read queries', function () {
    $user = User::factory()->create();

    $this->expectsDatabaseQueryCount(5);

    $server = (new CreateServer)->execute($user, new CreateServerData('My Server'));
    $baseRole = $server->baseRole;

    expect($baseRole->server_id)->toBe($server->id)
        ->and($baseRole->name)->toBe(ServerRole::BASE_ROLE_NAME)
        ->and($baseRole->is_system)->toBeTrue()
        ->and($baseRole->permissions)->toBe(AppPermission::basePermissions())
        ->and($server->base_role_id)->toBe($baseRole->id)
        ->and($server->isDirty('base_role_id'))->toBeFalse();
});

test('name is required', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/servers', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    expect(Server::count())->toBe(0);
});

test('name must be a string', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/servers', ['name' => 12345])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('name may not exceed 255 characters', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/servers', ['name' => str_repeat('a', 256)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('name at 255 characters is accepted', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/servers', ['name' => str_repeat('a', 255)])
        ->assertCreated();

    expect(Server::count())->toBe(1);
});
