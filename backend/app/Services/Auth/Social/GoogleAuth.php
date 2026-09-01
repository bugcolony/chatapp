<?php

namespace App\Services\Auth\Social;

use App\Enums\AuthProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAuth extends SocialAuth
{
    public function __construct()
    {
        parent::__construct(AuthProvider::Google);
    }

    protected function hasVerifiedEmail(SocialiteUser $callbackUser): bool
    {
        return $callbackUser->getEmail() !== null
            && ($callbackUser->user['email_verified'] ?? false) === true;
    }
}
