<?php

namespace App\Services\Gateway;

use InvalidArgumentException;
use JsonSerializable;

final readonly class Route implements JsonSerializable
{
    private function __construct(public ?int $serverId, public array $userIds) {}

    public static function server(int $serverId): self
    {
        if ($serverId <= 0) {
            throw new InvalidArgumentException('Invalid server id');
        }

        return new self($serverId, []);
    }

    public static function users(int ...$userIds): self
    {
        $userIds = array_values(array_unique($userIds));

        if ($userIds === [] || min($userIds) <= 0) {
            throw new InvalidArgumentException('Invalid user ids');
        }

        return new self(null, $userIds);
    }

    public function jsonSerialize(): array
    {
        return $this->serverId !== null
            ? ['server_id' => $this->serverId]
            : ['user_ids' => $this->userIds];
    }
}
