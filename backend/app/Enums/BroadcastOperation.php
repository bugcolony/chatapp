<?php

namespace App\Enums;

enum BroadcastOperation: int
{
    case MESSAGE_CREATED = 1;
    case CHANNEL_CREATED = 2;
    case CHANNEL_UPDATED = 3;
    case CHANNEL_DELETED = 4;
    case USER_JOINED_VOICE = 5;
    case USER_LEFT_VOICE = 6;
    case VOICE_CHANNEL_CLOSED = 7;
}
