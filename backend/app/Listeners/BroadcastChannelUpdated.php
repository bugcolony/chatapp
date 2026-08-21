<?php

namespace App\Listeners;

use App\Events\ChannelUpdated;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;

class BroadcastChannelUpdated
{
    public function __construct(private readonly RealtimeTransport $transport) {}

    public function handle(ChannelUpdated $event): void
    {
        $this->transport->publish(GatewayEvent::channelUpdated($event->channel));
    }
}
