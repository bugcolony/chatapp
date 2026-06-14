<?php

namespace App\Services\Auth\Social;

use App\Enums\AuthProvider;
use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Spatie\Permission\Models\Role;
use Throwable;

abstract class SocialAuth implements SocialAuthInterface
{
    public function __construct(
        protected readonly AuthProvider $provider,
    ) {}

    /**
     * @throws Throwable
     */
    public function handleCallback(SocialiteUser $callbackUser): User
    {
        return DB::transaction(function () use ($callbackUser): User {
            $user = User::query()->firstOrCreate([
                'email' => $callbackUser->getEmail(),
            ], [
                'name' => $callbackUser->getNickname() ?: $callbackUser->getName(),
                'email_verified_at' => now(),
                'password' => Str::password(),
            ]);

            $user->socialAccounts()->updateOrCreate([
                'user_id' => $user->id,
                'provider' => $this->provider,
            ], [
                'provider_id' => $callbackUser->getId(),
                'token' => $callbackUser->token,
                'refresh_token' => $callbackUser->refreshToken,
            ]);

            if ($user->wasRecentlyCreated) {
                $user->assignRole(Role::findOrCreate(SystemRole::User->value));
            }

            return $user;
        });
    }
}
