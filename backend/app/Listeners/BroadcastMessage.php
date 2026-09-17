<?php

namespace App\Listeners;

use App\Events\MessageCreated;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;

readonly class BroadcastMessage
{
    public function __construct(private RealtimeTransport $transport) {}

    public function handle(MessageCreated $event): void
    {
        $this->transport->publish(GatewayEvent::messageCreated($event->message, $event->channel));
    }
}
