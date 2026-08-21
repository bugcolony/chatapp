<?php

use App\Enums\ChannelType;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RedisPubSubTransport;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Facades\Exceptions;

test('events are published to the configured channel on the realtime connection', function () {
    $connection = Mockery::mock();
    $connection->shouldReceive('publish')
        ->once()
        ->withArgs(function (string $channel, string $payload): bool {
            return $channel === 'test.channel'
                && json_decode($payload, true, flags: JSON_THROW_ON_ERROR)['targetServerId'] === 12;
        });

    $redis = Mockery::mock(RedisFactory::class);
    $redis->shouldReceive('connection')->once()->with('realtime')->andReturn($connection);

    new RedisPubSubTransport($redis, 'test.channel')->publish(
        GatewayEvent::channelDeleted(channelId: 56, serverId: 12, type: ChannelType::Text),
    );
});

test('publishing nothing never touches redis', function () {
    $redis = Mockery::mock(RedisFactory::class);
    $redis->shouldNotReceive('connection');

    new RedisPubSubTransport($redis, 'test.channel')->publish();

    expect(true)->toBeTrue();
});

test('a gateway outage is reported instead of failing the caller', function () {
    Exceptions::fake();

    $connection = Mockery::mock();
    $connection->shouldReceive('publish')->once()->andThrow(new RuntimeException('redis down'));

    $redis = Mockery::mock(RedisFactory::class);
    $redis->shouldReceive('connection')->once()->andReturn($connection);

    new RedisPubSubTransport($redis, 'test.channel')->publish(
        GatewayEvent::channelDeleted(channelId: 56, serverId: 12, type: ChannelType::Text),
    );

    Exceptions::assertReported(RuntimeException::class);
});
