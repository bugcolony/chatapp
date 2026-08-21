<?php

namespace App\Listeners;

use App\Events\ChannelCreated;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;

class BroadcastChannelCreated
{
    public function __construct(private readonly RealtimeTransport $transport) {}

    public function handle(ChannelCreated $event): void
    {
        $this->transport->publish(GatewayEvent::channelCreated($event->channel));
    }
}
