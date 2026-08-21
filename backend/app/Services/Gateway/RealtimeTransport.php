<?php

namespace App\Services\Gateway;

interface RealtimeTransport
{
    public function publish(GatewayEvent ...$event): void;
}
