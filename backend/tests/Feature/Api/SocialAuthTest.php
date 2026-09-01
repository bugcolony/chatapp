<?php

use App\Enums\AuthProvider;
use App\Enums\SystemRole;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;

uses(RefreshDatabase::class);

function mockSocialiteUser(
    int|string $id,
    ?string $name,
    ?string $email,
    ?string $nickname,
    string $token,
    ?string $refreshToken,
): Laravel\Socialite\Two\User {
    $socialiteUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $socialiteUser->shouldReceive('getId')->andReturn($id);
    $socialiteUser->shouldReceive('getName')->andReturn($name);
    $socialiteUser->shouldReceive('getEmail')->andReturn($email);
    $socialiteUser->shouldReceive('getNickname')->andReturn($nickname);
    $socialiteUser->token = $token;
    $socialiteUser->refreshToken = $refreshToken;

    return $socialiteUser;
}

function mockGithubRedirectProvider(): void
{
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('redirect')
        ->andReturn(redirect()->away('https://github.com/login/oauth/authorize'));

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);
}

function mockGithubCallbackProvider(Laravel\Socialite\Two\User $socialiteUser): void
{
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);
}

function mockGithubCancelledCallbackProvider(): void
{
    $provider = Mockery::mock(Provider::class);
    $provider->shouldNotReceive('user');

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);
}

test('login redirects to provider', function () {
    mockGithubRedirectProvider();

    $response = $this->get('/auth/github/redirect');

    $response->assertStatus(302);
    expect($response->headers->get('Location'))->toContain('github.com');
});

test('callback creates a new user and social account and logs the user in with the session', function () {
    mockGithubCallbackProvider(mockSocialiteUser(12345, 'Test User', 'test@example.com', 'testuser', 'mock-token', 'mock-refresh-token'));

    $response = $this->get('/auth/github/callback');

    $response->assertStatus(302);

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and($user->username)->toBeNull()
        ->and($user->onboarded_at)->toBeNull()
        ->and($user->isOnboarded())->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasRole(SystemRole::User->value))->toBeTrue();

    $account = SocialAccount::where('user_id', $user->id)->first();
    expect($account)->not->toBeNull()
        ->and($account->provider->value)->toBe('github')
        ->and($account->provider_id)->toBe('12345')
        ->and($account->token)->toBe('mock-token')
        ->and($account->refresh_token)->toBe('mock-refresh-token');

    $this->assertAuthenticatedAs($user, 'web');
    $response->assertRedirect(config('app.frontend_url').'/login/process');
});

test('callback attaches a provider account to an existing user with the same email', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'test@example.com',
    ]);

    mockGithubCallbackProvider(mockSocialiteUser(777, 'Updated Name', 'test@example.com', 'updateduser', 'new-token', 'refresh-777'));

    $response = $this->get('/auth/github/callback');

    $response->assertStatus(302);

    expect(User::count())->toBe(1);

    $user->refresh();
    $this->assertAuthenticatedAs($user, 'web');
    expect($user->name)->toBe('Old Name');

    $account = $user->socialAccounts()->first();
    expect($account)->not->toBeNull()
        ->and($account->provider)->toBe(AuthProvider::GitHub)
        ->and($account->provider_id)->toBe('777')
        ->and($account->token)->toBe('new-token');
});

test('callback updates token on returning user with the same email and provider', function () {
    $user = User::factory()->create([
        'name' => 'Before Update',
        'email' => 'returning@example.com',
    ]);

    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => AuthProvider::GitHub,
        'provider_id' => '999',
        'token' => 'old-token',
        'refresh_token' => 'old-refresh',
    ]);

    mockGithubCallbackProvider(mockSocialiteUser(999, 'Same User', 'returning@example.com', 'returninguser', 'updated-token', 'updated-refresh'));

    $response = $this->get('/auth/github/callback');

    $response->assertStatus(302);

    expect(User::count())->toBe(1)
        ->and(SocialAccount::count())->toBe(1);

    $user->refresh();
    $this->assertAuthenticatedAs($user, 'web');
    $account = $user->socialAccounts()->first();

    expect($account->token)->toBe('updated-token')
        ->and($account->refresh_token)->toBe('updated-refresh');
});

test('callback redirects to the frontend with provider error details when the user cancels authorization', function () {
    mockGithubCancelledCallbackProvider();

    $response = $this->get('/auth/github/callback?error=access_denied&error_description=The+user+denied+your+request');

    $response->assertRedirect(config('app.frontend_url').'/login?provider=github&error=access_denied&error_description=The%20user%20denied%20your%20request');
    $this->assertGuest('web');
});

test('invalid auth providers are rejected', function () {
    $response = $this->get('/auth/invalid/redirect');

    $response->assertStatus(404);
});

test('callback redirects to login when the provider request fails', function () {
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andThrow(new RuntimeException('Provider unavailable'));

    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect(config('app.frontend_url').'/login?error=auth_failed');
    $this->assertGuest('web');
});

test('callback matches the existing account by provider id when the provider email changed', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => AuthProvider::GitHub,
        'provider_id' => '4242',
    ]);

    mockGithubCallbackProvider(mockSocialiteUser(4242, 'Same Person', 'new@example.com', 'sameperson', 'token', 'refresh'));

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect(config('app.frontend_url').'/login/process');
    $this->assertAuthenticatedAs($user, 'web');

    expect(User::count())->toBe(1)
        ->and(SocialAccount::count())->toBe(1)
        ->and($user->refresh()->email)->toBe('old@example.com');
});

test('callback refuses to create an account without a verified email', function () {
    mockGithubCallbackProvider(mockSocialiteUser(555, 'No Email', null, 'noemail', 'token', 'refresh'));

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect(config('app.frontend_url').'/login?error=email_unverified');

    $this->assertGuest('web');
    expect(User::count())->toBe(0)
        ->and(SocialAccount::count())->toBe(0);
});

test('a blocked email domain cannot create an account', function () {
    config()->set('signup.blocked_email_domains', ['users.noreply.github.com']);

    mockGithubCallbackProvider(mockSocialiteUser(818, 'Private Person', '818+ghost@USERS.NOREPLY.GITHUB.COM', 'ghost', 'token', 'refresh'));

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect(config('app.frontend_url').'/login?error=email_not_allowed');
    $this->assertGuest('web');

    expect(User::count())->toBe(0)
        ->and(SocialAccount::count())->toBe(0);
});

test('an existing account on a blocked domain can still sign in by provider id', function () {
    config()->set('signup.blocked_email_domains', ['users.noreply.github.com']);

    $user = User::factory()->create(['email' => '900+old@users.noreply.github.com']);
    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => AuthProvider::GitHub,
        'provider_id' => '900',
    ]);

    mockGithubCallbackProvider(mockSocialiteUser(900, 'Old Person', '900+old@users.noreply.github.com', 'old', 'token', 'refresh'));

    $this->get('/auth/github/callback')
        ->assertRedirect(config('app.frontend_url').'/login/process');

    $this->assertAuthenticatedAs($user, 'web');
    expect(User::count())->toBe(1);
});

test('an existing account on a blocked domain can still sign in by email', function () {
    config()->set('signup.blocked_email_domains', ['users.noreply.github.com']);

    $user = User::factory()->create(['email' => '901+old@users.noreply.github.com']);

    mockGithubCallbackProvider(mockSocialiteUser(901, 'Old Person', '901+old@users.noreply.github.com', 'old', 'token', 'refresh'));

    $this->get('/auth/github/callback')
        ->assertRedirect(config('app.frontend_url').'/login/process');

    $this->assertAuthenticatedAs($user, 'web');
    expect(User::count())->toBe(1)
        ->and(SocialAccount::count())->toBe(1);
});
