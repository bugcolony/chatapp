<?php

namespace App\Actions\Direct;

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\User;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;
use App\Exceptions\InvalidGroupOperation;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateGroupChannel
{
    public const MAX_PARTICIPANTS = 10;

    public function __construct(private RealtimeTransport $transport) {}

    /**
     * @throws Throwable
     */
    public function execute(User $owner, array $participantIds): Channel
    {
        $memberIds = $this->resolveMemberIds($owner, $participantIds);

        $channel = DB::transaction(static function () use ($owner, $memberIds): Channel {
            $channel = Channel::create([
                'type' => ChannelType::GROUP_DM,
                'owner_id' => $owner->id,
            ]);

            $channel->participants()->attach($memberIds);

            return $channel;
        });

        $channel->load('participants');

        $this->transport->publish(GatewayEvent::groupChannelCreated($channel, $memberIds));

        return $channel;
    }

    /**
     * @throws InvalidGroupOperation
     */
    private function resolveMemberIds(User $owner, array $participantIds): array
    {
        $invitedIds = array_values(array_diff(
            array_unique(array_map('intval', $participantIds)),
            [$owner->id],
        ));

        if ($invitedIds === []) {
            throw new InvalidGroupOperation('A group needs at least one other participant.');
        }

        $friendIds = $owner->friends()->pluck('users.id')->all();

        if (array_diff($invitedIds, $friendIds) !== []) {
            throw new InvalidGroupOperation('Participants must be friends.');
        }

        $memberIds = [$owner->id, ...$invitedIds];

        if (count($memberIds) > self::MAX_PARTICIPANTS) {
            throw new InvalidGroupOperation('Group is full.');
        }

        return $memberIds;
    }
}
