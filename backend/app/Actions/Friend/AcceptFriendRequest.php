<?php

namespace App\Actions\Friend;

use App\Enums\ChannelType;
use App\Enums\FriendStatus;
use App\Models\Channel;
use App\Models\Friend;
use App\Models\User;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class AcceptFriendRequest
{
    public function __construct(private RealtimeTransport $transport) {}

    /**
     * @throws Throwable
     */
    public function execute(User $user, User $friend): bool
    {
        $channel = DB::transaction(static function () use ($user, $friend): ?Channel {
            $accepted = Friend::query()
                ->between($user->id, $friend->id)
                ->where('status', FriendStatus::INCOMING_PENDING)
                ->update(['status' => FriendStatus::FRIEND]);

            if (! $accepted) {
                return null;
            }

            Friend::query()
                ->between($friend->id, $user->id)
                ->update(['status' => FriendStatus::FRIEND]);

            $channel = Channel::query()
                ->where('type', ChannelType::DIRECT_MESSAGE)
                ->whereHas('participants', fn ($query) => $query->where('user_id', $friend->id))
                ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
                ->first();

            if (! $channel) {
                $channel = Channel::create(['type' => ChannelType::DIRECT_MESSAGE]);

                $channel->participants()->attach([$user->id, $friend->id]);
            }

            return $channel;
        });

        if (! $channel) {
            return false;
        }

        $this->transport->publish(GatewayEvent::friendAdded($user, $friend, $channel->id));

        return true;
    }
}
