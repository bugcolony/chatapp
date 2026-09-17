<?php

namespace App\Enums;

enum BroadcastOperation: int
{
    case MESSAGE_CREATED = 1;
    case CHANNEL_CREATED = 2;
    case CHANNEL_UPDATED = 3;
    case CHANNEL_DELETED = 4;
    case VOICE_USER_JOINED = 5;
    case VOICE_USER_LEFT = 6;
    case VOICE_CHANNEL_CLOSED = 7;
    case FRIEND_REQUEST_RECEIVED = 8;
    case FRIEND_ADDED = 9;
    case FRIEND_REMOVED = 10;
}
