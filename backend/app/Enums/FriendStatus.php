<?php

namespace App\Enums;

enum FriendStatus: int
{
    case FRIEND = 1;
    case OUTGOING_PENDING = 2;
    case INCOMING_PENDING = 3;
    case BLOCKED = 4;
}
