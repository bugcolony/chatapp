<?php

namespace App\Enums;

enum ChannelType: string
{
    case Text = 'text';
    case Voice = 'voice';
    case Category = 'category';
    case VoiceText = 'voice_text';
}
