<?php

namespace App\Enums;

enum ChannelType: string
{
    case TEXT = 'text';
    case VOICE = 'voice';
    case CATEGORY = 'category';
    case DIRECT_MESSAGE = 'direct_message';

    public function supportsMessages(): bool
    {
        return match ($this) {
            self::TEXT, self::DIRECT_MESSAGE, self::VOICE => true,
            default => false,
        };
    }
}
