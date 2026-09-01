<?php

namespace App\Services\Auth\Social;

use App\Enums\AuthProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GithubAuth extends SocialAuth
{
    public function __construct()
    {
        parent::__construct(AuthProvider::GitHub);
    }

    protected function hasVerifiedEmail(SocialiteUser $callbackUser): bool
    {
        return $callbackUser->getEmail() !== null;
    }
}
