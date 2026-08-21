<?php

namespace App\Services\Gateway;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class RedisWebSocketTicketStore implements WebSocketTicketStore
{
    private const string CONNECTION = 'realtime';

    private const string KEY_PREFIX = 'ticket:';

    private const int TTL_SECONDS = 60;

    private const int TICKET_LENGTH = 32;

    public function __construct(private readonly RedisFactory $redis) {}

    /**
     * @throws JsonException
     */
    public function issue(int $userId, array $serverIds): string
    {
        $ticket = Str::random(self::TICKET_LENGTH);

        $payload = json_encode([
            'id' => $userId,
            'serverSubscriptions' => $serverIds,
        ], JSON_THROW_ON_ERROR);

        $stored = $this->redis->connection(self::CONNECTION)->setex(
            self::KEY_PREFIX.$ticket,
            self::TTL_SECONDS,
            $payload,
        );

        if (! $stored) {
            throw new RuntimeException('Unable to set WS ticket in redis.');
        }

        return $ticket;
    }
}
