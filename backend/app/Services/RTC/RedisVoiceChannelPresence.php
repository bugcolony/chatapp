<?php

namespace App\Services\RTC;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;

class RedisVoiceChannelPresence implements VoiceChannelPresence
{
    private const string CONNECTION = 'realtime';

    private const string KEY_PREFIX = 'voice:channel:';

    public function __construct(private readonly RedisFactory $redis) {}

    public function join(int $channelId, int $userId): void
    {
        $this->connection()->sadd($this->key($channelId), $userId);
    }

    public function leave(int $channelId, int $userId): void
    {
        $this->connection()->srem($this->key($channelId), $userId);
    }

    public function clear(int $channelId): void
    {
        $this->connection()->del($this->key($channelId));
    }

    public function snapshot(int ...$channelIds): array
    {
        if ($channelIds === []) {
            return [];
        }

        $results = $this->connection()->pipeline(function ($pipe) use ($channelIds): void {
            foreach ($channelIds as $channelId) {
                $pipe->smembers($this->key($channelId));
            }
        });

        $snapshot = [];

        foreach (array_values($channelIds) as $index => $channelId) {
            $members = array_map(intval(...), $results[$index] ?? []);

            if ($members !== []) {
                $snapshot[$channelId] = $members;
            }
        }

        return $snapshot;
    }

    private function connection(): Connection
    {
        return $this->redis->connection(self::CONNECTION);
    }

    private function key(int $channelId): string
    {
        return self::KEY_PREFIX.$channelId;
    }
}
