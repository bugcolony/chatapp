<?php

namespace App\Services\Auth\Social;

use App\Enums\AuthProvider;

class GithubAuth extends SocialAuth
{
    public function __construct()
    {
        parent::__construct(AuthProvider::GitHub);
    }
}
