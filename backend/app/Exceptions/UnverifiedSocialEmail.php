<?php

namespace App\Exceptions;

use App\Enums\AuthProvider;
use RuntimeException;

final class UnverifiedSocialEmail extends RuntimeException
{
    public static function forProvider(AuthProvider $provider): self
    {
        return new self("[{$provider->value}] did not supply a verified email address.");
    }
}
