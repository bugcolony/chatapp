<?php

namespace App\Services\Gateway;

use InvalidArgumentException;

class BroadcastTarget
{
    public function __construct(public int $serverId, public ?int $channelId = null, public ?int $senderId = null)
    {
        if ($serverId <= 0) {
            throw new InvalidArgumentException('Invalid server id');
        }
    }

    public static function server(int $serverId): self
    {
        return new self($serverId);
    }

    public static function channel(int $serverId, int $channelId): self
    {
        return new self($serverId, $channelId);
    }

    public function sender(int $senderId): self
    {
        return new self($this->serverId, $this->channelId, $senderId);
    }

    public function toArray(): array
    {
        $targets = [
            'targetServerId' => $this->serverId,
        ];

        if ($this->channelId !== null) {
            $targets['targetChannelId'] = $this->channelId;
        }

        if ($this->senderId !== null) {
            $targets['senderId'] = $this->senderId;
        }

        return $targets;
    }
}
