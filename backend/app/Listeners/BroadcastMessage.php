<?php

namespace App\Listeners;

use App\Events\MessageCreated;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;

class BroadcastMessage
{
    public function __construct(private readonly RealtimeTransport $transport) {}

    public function handle(MessageCreated $event): void
    {
        $this->transport->publish(GatewayEvent::messageCreated($event->message));
    }
}
