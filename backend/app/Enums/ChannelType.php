<?php

namespace App\Enums;

enum ChannelType: string
{
    case TEXT = 'text';
    case VOICE = 'voice';
    case CATEGORY = 'category';
    case DIRECT_MESSAGE = 'direct_message';
    case GROUP_DM = 'group_dm';

    public function supportsMessages(): bool
    {
        return match ($this) {
            self::TEXT, self::DIRECT_MESSAGE, self::GROUP_DM, self::VOICE => true,
            default => false,
        };
    }

    public function isDirect(): bool
    {
        return match ($this) {
            self::DIRECT_MESSAGE, self::GROUP_DM => true,
            default => false,
        };
    }
}
