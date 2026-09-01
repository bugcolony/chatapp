<?php

namespace App\Exceptions;

use RuntimeException;

final class DisallowedSocialEmail extends RuntimeException
{
    public static function forDomain(string $domain): self
    {
        return new self("Sign up was refused for the blocked email domain [{$domain}].");
    }
}
