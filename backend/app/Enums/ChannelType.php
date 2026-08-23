<?php

namespace App\Enums;

enum ChannelType: string
{
    case Text = 'text';
    case Voice = 'voice';
    case Category = 'category';
    case VoiceText = 'voice_text';


    public function supportsMessages(): bool
    {
        return match ($this) {
            self::Text, self::VoiceText => true,
            default => false,
        };
    }
}
