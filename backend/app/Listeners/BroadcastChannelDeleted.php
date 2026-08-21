<?php

namespace App\Listeners;

use App\Events\ChannelDeleted;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;

readonly class BroadcastChannelDeleted
{
    public function __construct(private RealtimeTransport $transport) {}

    public function handle(ChannelDeleted $event): void
    {
        $this->transport->publish(GatewayEvent::channelDeleted(
            $event->channelId,
            $event->serverId,
            $event->type,
        ));
    }
}
