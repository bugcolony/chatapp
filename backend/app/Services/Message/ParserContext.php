<?php

namespace App\Services\Message;

readonly class ParserContext
{
    public function __construct(public ?string $serverId = null, public ?string $channelId = null)
    {
    }
}
