<?php

namespace App\Services\Auth\Social;

use App\Enums\AuthProvider;

class GoogleAuth extends SocialAuth
{
    public function __construct()
    {
        parent::__construct(AuthProvider::Google);
    }
}
