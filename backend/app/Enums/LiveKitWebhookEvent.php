<?php

namespace App\Enums;

enum LiveKitWebhookEvent: string
{
    case PARTICIPANT_JOINED = 'participant_joined';
    case PARTICIPANT_LEFT = 'participant_left';
    case ROOM_FINISHED = 'room_finished';
}
