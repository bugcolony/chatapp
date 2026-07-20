<?php

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Events\ChannelDeleted;
use App\Listeners\BroadcastChannelDeleted;
use Illuminate\Support\Facades\Redis;

test('channel deleted payload targets every client subscribed to the server', function () {
    $client = Mockery::mock();
    $connection = Mockery::mock();

    Redis::shouldReceive('connection')->once()->with('realtime')->andReturn($connection);
    $connection->shouldReceive('client')->once()->andReturn($client);
    $client->shouldReceive('publish')->once()->withArgs(function (string $topic, string $payload): bool {
        $operation = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        return $topic === 'messages.created'
            && $operation['op'] === BroadcastOperation::CHANNEL_DELETED->value
            && $operation['targetServerId'] === 12
            && $operation['data'] === [
                'id' => 56,
                'type' => ChannelType::Text->value,
            ];
    });

    (new BroadcastChannelDeleted)->handle(new ChannelDeleted(
        channelId: 56,
        serverId: 12,
        type: ChannelType::Text,
    ));
});
