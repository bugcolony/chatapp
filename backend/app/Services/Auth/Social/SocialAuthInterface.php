<?php

namespace App\Services\Auth\Social;

use App\Models\User as UserModel;
use Laravel\Socialite\Contracts\User;

interface SocialAuthInterface
{
    public function handleCallback(User $callbackUser): UserModel;
}
