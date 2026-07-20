<?php

namespace App\Events;

use App\Models\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Channel $channel)
    {
        //
    }
}
