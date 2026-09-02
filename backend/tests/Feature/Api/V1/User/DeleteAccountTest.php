<?php

use App\Models\Member;
use App\Models\Server;
use App\Models\ServerInvite;
use App\Models\User;
use App\Services\DemoFixtureManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
});

test('the close endpoint is rate limited', function () {
    $route = collect(Route::getRoutes())->first(
        fn ($route) => $route->uri() === 'api/v1/me' && in_array('DELETE', $route->methods(), true),
    );

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:3,1');
});

function loginAs(User $user): void
{
    test()->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();
}

test('closing the account kills the session it was closed from', function () {
    $user = User::factory()->create(['username' => 'ada']);

    loginAs($user);

    $this->getJson('/api/v1/me')->assertOk();

    $this->deleteJson('/api/v1/me', ['username' => 'ada'])->assertOk();

    $this->getJson('/api/v1/me')->assertUnauthorized();
});

test('a session on another device is rejected once the account is closed', function () {
    $user = User::factory()->create(['username' => 'ada']);

    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/me', ['username' => 'ada'])->assertOk();

    Sanctum::actingAs($user->fresh());

    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->getJson('/api/v1/servers')->assertUnauthorized();
});

test('the username has to match', function () {
    $user = User::factory()->create(['username' => 'ada']);

    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/me', ['username' => 'grace'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('username');

    $this->deleteJson('/api/v1/me')
        ->assertStatus(422)
        ->assertJsonValidationErrors('username');

    expect($user->fresh()->isClosed())->toBeFalse();
});

test('the username confirmation is case and whitespace tolerant', function () {
    $user = User::factory()->create(['username' => 'ada']);

    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/me', ['username' => '  ADA '])->assertOk();

    expect($user->fresh()->isClosed())->toBeTrue();
});

test('a user who never onboarded can still close their account', function () {
    $user = User::factory()->notOnboarded()->create();

    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/me')->assertOk();

    expect($user->fresh()->isClosed())->toBeTrue();
});

test('closing scrubs the profile and leaves every server', function () {
    $user = User::factory()->create(['username' => 'ada', 'name' => 'Ada']);
    $server = Server::factory()->create();

    Member::factory()->create([
        'user_id' => $user->id,
        'server_id' => $server->id,
        'nickname' => 'Ada',
        'left_at' => null,
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/me', ['username' => 'ada'])->assertOk();

    $user->refresh();

    expect($user->name)->toBe('Deleted User')
        ->and($user->username)->toBe("deleted-{$user->id}")
        ->and($user->email)->toBe("deleted-{$user->id}@deleted.invalid");

    $member = Member::where('user_id', $user->id)->first();

    expect($member->nickname)->toBe('Deleted User')
        ->and($member->left_at)->not->toBeNull();
});

test('the profile payload carries the owned server count for the warning', function () {
    $user = User::factory()->create();

    Server::factory()->count(2)->create(['user_id' => $user->id]);
    Server::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('owned_servers_count', 2);
});

test('demo accounts cannot be closed', function () {
    $user = User::factory()->create([
        'username' => 'example_user_1',
        'email' => DemoFixtureManager::OWNER_EMAIL,
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/me', ['username' => 'example_user_1'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Demo accounts cannot be deleted.');

    expect($user->fresh()->isClosed())->toBeFalse();
});

test('the profile payload flags demo accounts so the button can be hidden', function () {
    Sanctum::actingAs(User::factory()->create(['email' => DemoFixtureManager::OWNER_EMAIL]));

    $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('is_demo', true);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('is_demo', false);
});

test('invite codes created by the account stop working once it is closed', function () {
    $user = User::factory()->create(['username' => 'ada']);
    $server = Server::factory()->create(['user_id' => $user->id]);

    $invite = ServerInvite::query()->create([
        'server_id' => $server->id,
        'created_by' => $user->id,
        'code' => 'ADACODE123',
    ]);

    $this->getJson('/api/v1/invites/ADACODE123')->assertOk();

    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/me', ['username' => 'ada'])->assertOk();

    expect(ServerInvite::find($invite->id))->toBeNull();

    $this->getJson('/api/v1/invites/ADACODE123')->assertNotFound();
});

test('owned servers survive the close and stay on the tombstone row', function () {
    $user = User::factory()->create(['username' => 'ada']);
    $server = Server::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/me', ['username' => 'ada'])->assertOk();

    expect($server->fresh()->user_id)->toBe($user->id);
});
