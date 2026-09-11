<?php

namespace App\Actions\Friend;

use App\Enums\FriendStatus;
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
        return DB::transaction(static function () use ($user, $friend): bool {
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

            return true;
        });
    }
}
