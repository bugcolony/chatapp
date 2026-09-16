<?php

namespace App\Events;

use App\Models\Channel;
use App\Models\Message;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Message $message, public readonly Channel $channel)
    {
        //
    }
}
