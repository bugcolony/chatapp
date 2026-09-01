<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class FileStorageException extends RuntimeException
{
    public static function writeFailed(string $disk, ?Throwable $previous = null): self
    {
        return new self(
            "Unable to store a file on disk [{$disk}].",
            previous: $previous,
        );
    }
}
