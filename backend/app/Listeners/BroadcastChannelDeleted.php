<?php

namespace App\Listeners;

use App\Enums\BroadcastOperation;
use App\Events\ChannelDeleted;
use Illuminate\Support\Facades\Redis;
use JsonException;

class BroadcastChannelDeleted
{
    /**
     * @throws JsonException
     */
    public function handle(ChannelDeleted $event): void
    {
        Redis::connection('realtime')->client()->publish(
            'messages.created',
            json_encode([
                'op' => BroadcastOperation::CHANNEL_DELETED,
                'targetServerId' => $event->serverId,
                'data' => [
                    'id' => $event->channelId,
                    'type' => $event->type,
                ],
            ], JSON_THROW_ON_ERROR),
        );
    }
}
