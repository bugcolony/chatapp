<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class MessageAttachmentStorageException extends RuntimeException
{
    public static function writeFailed(string $disk, ?Throwable $previous = null): self
    {
        return new self(
            "Unable to store a message attachment on disk [{$disk}].",
            previous: $previous,
        );
    }
}
