<?php

namespace App\Services\Gateway;

interface WebSocketTicketStore
{
    /**
     * @param  list<int>  $serverIds
     * @return string The single use ticket the client presents to the gateway.
     */
    public function issue(int $userId, array $serverIds): string;
}
