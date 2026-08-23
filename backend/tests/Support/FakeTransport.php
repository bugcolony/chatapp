<?php

namespace Tests\Support;

use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;

class FakeTransport implements RealtimeTransport
{
    public array $published = [];

    public function publish(GatewayEvent ...$event): void
    {
        array_push($this->published, ...$event);
    }

    public function sole(): GatewayEvent
    {
        expect($this->published)->toHaveCount(1);

        return $this->published[0];
    }
}
