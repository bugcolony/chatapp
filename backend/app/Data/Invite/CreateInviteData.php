<?php

namespace App\Data\Invite;

use Illuminate\Contracts\Support\Arrayable;

final readonly class CreateInviteData implements Arrayable
{
    public function __construct(
        public ?int $userId = null,
        public ?int $serverId = null,
        public ?int $maxUses = null,
        public ?string $expiresAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'server_id' => $this->serverId,
            'max_uses' => $this->maxUses,
            'expires_at' => $this->expiresAt,
        ];
    }
}
