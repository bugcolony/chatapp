<?php

namespace App\Listeners;

use App\Enums\BroadcastOperation;
use App\Events\ChannelUpdated;
use Illuminate\Support\Facades\Redis;
use JsonException;

class BroadcastChannelUpdated
{
    /**
     * @throws JsonException
     */
    public function handle(ChannelUpdated $event): void
    {
        $channel = $event->channel;

        Redis::connection('realtime')->client()->publish(
            'messages.created',
            json_encode([
                'op' => BroadcastOperation::CHANNEL_UPDATED,
                'targetServerId' => $channel->server_id,
                'data' => [
                    'id' => $channel->id,
                    'server_id' => $channel->server_id,
                    'parent_id' => $channel->parent_id,
                    'type' => $channel->type,
                    'name' => $channel->name,
                ],
            ], JSON_THROW_ON_ERROR),
        );
    }
}
