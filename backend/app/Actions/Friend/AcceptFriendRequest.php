<?php

namespace App\Actions\Friend;

use App\Enums\ChannelType;
use App\Enums\FriendStatus;
use App\Models\Channel;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

final class AcceptFriendRequest
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, User $friend): bool
    {
        $channel = Channel::where('type', ChannelType::DIRECT_MESSAGE)
            ->whereHas('participants', fn($query) => $query->where('user_id', $friend->id))
            ->whereHas('participants', fn($query) => $query->where('user_id', $user->id))
            ->first();

        return DB::transaction(static function () use ($user, $friend, $channel): bool {
            $accepted = Friend::query()
                ->between($user->id, $friend->id)
                ->where('status', FriendStatus::INCOMING_PENDING)
                ->update(['status' => FriendStatus::FRIEND]);

            if (! $accepted) {
                return false;
            }

            Friend::query()
                ->between($friend->id, $user->id)
                ->update(['status' => FriendStatus::FRIEND]);

            if (!$channel) {
                $channel = Channel::create(['type' => ChannelType::DIRECT_MESSAGE]);

                $channel->participants()->attach([$user, $friend]);
            }

            return true;
        });
    }
}
