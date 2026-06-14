<?php

use App\Enums\AuthProvider;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

test('it belongs to a user', function () {
    $sa = SocialAccount::factory()->create();

    expect($sa->user)->toBeInstanceOf(User::class);
});

test('a user can have multiple social accounts', function () {
    $user = User::factory()->create();

    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => AuthProvider::GitHub,
    ]);

    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => AuthProvider::Google,
    ]);

    expect($user->socialAccounts)->toHaveCount(2)
        ->and($user->socialAccounts->first())->toBeInstanceOf(SocialAccount::class);
});

test('token fields are hidden from serialization and stored encrypted', function () {
    $sa = SocialAccount::factory()->create([
        'token' => 'plain-token',
        'refresh_token' => 'plain-refresh-token',
    ]);

    $sa->refresh();

    expect($sa->toArray())
        ->not->toHaveKey('token')
        ->not->toHaveKey('refresh_token')
        ->and($sa->token)->toBe('plain-token')
        ->and($sa->refresh_token)->toBe('plain-refresh-token');

    $rawValues = SocialAccount::query()->findOrFail($sa->id)->getRawOriginal();

    expect($rawValues['token'])->not->toBe('plain-token')
        ->and($rawValues['refresh_token'])->not->toBe('plain-refresh-token')
        ->and(Crypt::decryptString($rawValues['token']))->toBe('plain-token')
        ->and(Crypt::decryptString($rawValues['refresh_token']))->toBe('plain-refresh-token');
});
