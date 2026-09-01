<?php

namespace App\Services\Auth\Social;

use App\Enums\AuthProvider;
use App\Enums\SystemRole;
use App\Exceptions\DisallowedSocialEmail;
use App\Exceptions\UnverifiedSocialEmail;
use App\Models\SocialAccount;
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
            $user = $this->resolveUser($callbackUser);

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

    abstract protected function hasVerifiedEmail(SocialiteUser $callbackUser): bool;

    /**
     * @throws UnverifiedSocialEmail|DisallowedSocialEmail
     */
    private function resolveUser(SocialiteUser $callbackUser): User
    {
        $account = SocialAccount::query()
            ->where('provider', $this->provider)
            ->where('provider_id', $callbackUser->getId())
            ->first();

        if ($account?->user !== null) {
            return $account->user;
        }

        if (! $this->hasVerifiedEmail($callbackUser)) {
            throw UnverifiedSocialEmail::forProvider($this->provider);
        }

        $email = $callbackUser->getEmail();
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            return $existing;
        }

        $this->guardAgainstBlockedDomain($email);

        return User::query()->create([
            'email' => $email,
            'name' => $callbackUser->getName() ?: $callbackUser->getNickname(),
            'email_verified_at' => now(),
            'password' => Str::password(),
        ]);
    }

    /**
     * @throws DisallowedSocialEmail
     */
    private function guardAgainstBlockedDomain(string $email): void
    {
        $domain = Str::lower(Str::afterLast($email, '@'));

        $blocked = array_map(
            static fn (string $blockedDomain): string => Str::lower($blockedDomain),
            config('signup.blocked_email_domains', []),
        );

        if (in_array($domain, $blocked, true)) {
            throw DisallowedSocialEmail::forDomain($domain);
        }
    }
}
