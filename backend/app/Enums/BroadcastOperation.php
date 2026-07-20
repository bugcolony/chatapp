<?php

namespace App\Enums;

enum BroadcastOperation: int
{
    case MESSAGE_CREATED = 1;
    case CHANNEL_CREATED = 2;
    case CHANNEL_UPDATED = 3;
    case CHANNEL_DELETED = 4;
}
