<?php

namespace App\Actions\Direct;

use App\Models\Channel;
use App\Models\User;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;
use App\Exceptions\InvalidGroupOperation;
use App\Exceptions\NotAGroupParticipant;
use Throwable;

final readonly class RemoveParticipant
{
    public function __construct(private RealtimeTransport $transport) {}

    /**
     * @throws Throwable
     */
    public function execute(Channel $channel, User $participant): Channel
    {
        if ($channel->owner_id === $participant->id) {
            throw new InvalidGroupOperation('The owner cannot be removed.');
        }

        if (! $channel->participants()->where('user_id', $participant->id)->exists()) {
            throw new NotAGroupParticipant();
        }

        $channel->participants()->detach($participant->id);

        $channel->load('participants');

        $remaining = $channel->participants->pluck('id')->all();

        $this->transport->publish(GatewayEvent::groupChannelRemoved($channel->id, [$participant->id]));

        if ($remaining !== []) {
            $this->transport->publish(GatewayEvent::groupChannelUpdated($channel, $remaining));
        }

        return $channel;
    }
}
