<?php

namespace App\Events;

use App\Enums\ChannelType;
use Illuminate\Foundation\Events\Dispatchable;

class ChannelDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $channelId,
        public readonly int $serverId,
        public readonly ChannelType $type,
    ) {
        //
    }
}
