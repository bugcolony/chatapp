<?php

namespace App\Enums;

enum ChannelType: string
{
    case Text = 'text';
    case Voice = 'voice';
    case Category = 'category';

    public function supportsMessages(): bool
    {
        return match ($this) {
            self::Text, self::Voice => true,
            default => false,
        };
    }
}
