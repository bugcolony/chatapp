<?php

namespace App\Services\Gateway;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Throwable;

class RedisPubSubTransport implements RealtimeTransport
{
    private const string CONNECTION = 'realtime';

    public function __construct(private readonly RedisFactory $redis, private readonly string $channel) {}

    public function publish(GatewayEvent ...$event): void
    {
        if (count($event) === 0) {
            return;
        }

        $connection = $this->redis->connection(self::CONNECTION);

        foreach ($event as $gatewayEvent) {
            try {
                $connection->publish($this->channel, json_encode($gatewayEvent, JSON_THROW_ON_ERROR));
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
