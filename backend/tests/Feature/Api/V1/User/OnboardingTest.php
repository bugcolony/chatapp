<?php

use App\Actions\User\CompleteOnboarding;
use App\Models\Member;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('a user without a username is blocked from the app', function () {
    Sanctum::actingAs(User::factory()->notOnboarded()->create());

    $this->getJson('/api/v1/servers')->assertForbidden();
    $this->postJson('/api/v1/servers', ['name' => 'My Server'])->assertForbidden();

    expect(Server::count())->toBe(0);
});

test('a user without a username can still read their profile and log out', function () {
    Sanctum::actingAs(User::factory()->notOnboarded()->create());

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('onboarded', false)
        ->assertJsonPath('username', null);

    $this->postJson('/api/v1/logout')->assertOk();
});

test('a user without a username cannot update their profile', function () {
    Sanctum::actingAs(User::factory()->notOnboarded()->create());

    $this->postJson('/api/v1/me', ['name' => 'New Name'])->assertForbidden();
});

test('an unauthenticated user cannot onboard', function () {
    $this->postJson('/api/v1/me/onboarding', ['username' => 'someone'])
        ->assertUnauthorized();
});

test('a user can complete onboarding by picking a username', function () {
    $user = User::factory()->notOnboarded()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/me/onboarding', ['username' => 'Cave.Man_1'])
        ->assertOk()
        ->assertJsonPath('username', 'cave.man_1')
        ->assertJsonPath('onboarded', true);

    $user->refresh();

    expect($user->username)->toBe('cave.man_1')
        ->and($user->name)->toBe('cave.man_1')
        ->and($user->onboarded_at)->not->toBeNull()
        ->and($user->isOnboarded())->toBeTrue();

    $this->getJson('/api/v1/servers')->assertOk();
});

test('an onboarded user cannot onboard again', function () {
    Sanctum::actingAs(User::factory()->create(['username' => 'taken_name']));

    $this->postJson('/api/v1/me/onboarding', ['username' => 'another_name'])
        ->assertStatus(409);
});

test('username must be unique regardless of case', function () {
    User::factory()->create(['username' => 'existing']);
    Sanctum::actingAs(User::factory()->notOnboarded()->create());

    $this->postJson('/api/v1/me/onboarding', ['username' => 'EXISTING'])
        ->assertJsonValidationErrorFor('username');
});

test('username is validated', function (mixed $username) {
    Sanctum::actingAs(User::factory()->notOnboarded()->create());

    $this->postJson('/api/v1/me/onboarding', ['username' => $username])
        ->assertJsonValidationErrorFor('username');
})->with([
    'missing' => [null],
    'too short' => ['ab'],
    'too long' => [str_repeat('a', 33)],
    'illegal characters' => ['cave man!'],
]);

test('profile updates cannot change the username', function () {
    $user = User::factory()->create(['username' => 'original']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/me', ['name' => 'New Name', 'username' => 'hijacked'])
        ->assertOk();

    expect($user->refresh()->username)->toBe('original');
});

test('a username claimed after validation is reported as a validation error', function () {
    $user = User::factory()->notOnboarded()->create();
    $taken = User::factory()->create(['username' => 'raced']);

    expect(fn () => app(CompleteOnboarding::class)->execute($user, $taken->username))
        ->toThrow(ValidationException::class);

    expect($user->refresh()->isOnboarded())->toBeFalse();
});

test('onboarding syncs the display name onto existing memberships', function () {
    $user = User::factory()->notOnboarded()->create(['name' => 'Provider Name']);
    $member = Member::factory()->create(['user_id' => $user->id, 'nickname' => 'Provider Name']);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/me/onboarding', ['username' => 'cave_man'])->assertOk();

    expect($member->refresh()->nickname)->toBe('cave_man');
});

test('reserved usernames are rejected', function (string $username) {
    Sanctum::actingAs(User::factory()->notOnboarded()->create());

    $this->postJson('/api/v1/me/onboarding', ['username' => $username])
        ->assertJsonValidationErrorFor('username');
})->with(['admin', 'support', 'system', 'onboarding']);
