<?php

namespace App\Services\RTC;

interface VoiceChannelPresence
{
    public function join(int $channelId, int $userId): void;

    public function leave(int $channelId, int $userId): void;

    public function clear(int $channelId): void;

    public function snapshot(int ...$channelIds): array;
}
