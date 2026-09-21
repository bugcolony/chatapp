<?php

namespace App\Actions\Direct;

use App\Models\Channel;
use App\Models\User;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;
use App\Exceptions\InvalidGroupOperation;
use Throwable;

final readonly class AddParticipant
{
    public function __construct(private RealtimeTransport $transport) {}

    /**
     * @throws Throwable
     */
    public function execute(Channel $channel, User $actor, User $participant): Channel
    {
        if ($channel->participants()->where('user_id', $participant->id)->exists()) {
            throw new InvalidGroupOperation('Already a participant.');
        }

        if ($channel->participants()->count() >= CreateGroupChannel::MAX_PARTICIPANTS) {
            throw new InvalidGroupOperation('Group is full.');
        }

        if (! $actor->friends()->where('users.id', $participant->id)->exists()) {
            throw new InvalidGroupOperation('Participants must be friends.');
        }

        $channel->participants()->attach($participant->id, [
            'hidden_before_message_id' => $channel->last_message_id ?? 0,
        ]);

        $channel->load('participants');

        $this->transport->publish(
            GatewayEvent::groupChannelUpdated($channel, $channel->participants->pluck('id')->all())
        );

        return $channel;
    }
}
