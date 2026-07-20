<?php

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Events\ChannelCreated;
use App\Listeners\BroadcastChannelCreated;
use App\Models\Channel;
use Illuminate\Support\Facades\Redis;

test('channel created payload targets every client subscribed to the server', function () {
    $channel = new Channel([
        'server_id' => 12,
        'parent_id' => 34,
        'name' => 'announcements',
        'type' => ChannelType::Text,
    ]);
    $channel->id = 56;

    $client = Mockery::mock();
    $connection = Mockery::mock();

    Redis::shouldReceive('connection')
        ->once()
        ->with('realtime')
        ->andReturn($connection);

    $connection->shouldReceive('client')
        ->once()
        ->andReturn($client);

    $client->shouldReceive('publish')
        ->once()
        ->withArgs(function (string $topic, string $payload): bool {
            $operation = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

            return $topic === 'messages.created'
                && $operation['op'] === BroadcastOperation::CHANNEL_CREATED->value
                && $operation['targetServerId'] === 12
                && $operation['data'] === [
                    'id' => 56,
                    'server_id' => 12,
                    'parent_id' => 34,
                    'type' => ChannelType::Text->value,
                    'name' => 'announcements',
                ];
        });

    (new BroadcastChannelCreated)->handle(new ChannelCreated($channel));
});
