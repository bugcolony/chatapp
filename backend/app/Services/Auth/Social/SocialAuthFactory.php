<?php

namespace App\Services\Auth\Social;

use App\Enums\AuthProvider;

class SocialAuthFactory
{
    public function make(AuthProvider $provider): SocialAuthInterface
    {
        return match ($provider) {
            AuthProvider::GitHub => app(GithubAuth::class),
            AuthProvider::Google => app(GoogleAuth::class),
        };
    }
}
