<?php

namespace App\Actions\Friend;

use App\Enums\FriendStatus;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Throwable;

final class SendFriendRequest
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, User $friend): FriendStatus
    {
        $existing = Friend::query()->between($user->id, $friend->id)->first();

        if ($existing?->status === FriendStatus::INCOMING_PENDING) {
            new AcceptFriendRequest()->execute($user, $friend);

            return FriendStatus::FRIEND;
        }

        $blocked = $existing?->status === FriendStatus::BLOCKED
            || Friend::query()
                ->between($friend->id, $user->id)
                ->where('status', FriendStatus::BLOCKED)
                ->exists();

        if ($blocked) {
            throw ValidationException::withMessages(['username' => "Couldn't send a friend request to that user."]);
        }

        if ($existing) {
            throw ValidationException::withMessages([
                'username' => $existing->status === FriendStatus::FRIEND
                    ? "You're already friends."
                    : 'Friend request already sent.',
            ]);
        }

        $now = now();

        try {
            Friend::query()->insert([
                ['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => FriendStatus::OUTGOING_PENDING->value, 'created_at' => $now, 'updated_at' => $now],
                ['user_id' => $friend->id, 'friend_id' => $user->id, 'status' => FriendStatus::INCOMING_PENDING->value, 'created_at' => $now, 'updated_at' => $now],
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->execute($user, $friend);
        }

        return FriendStatus::OUTGOING_PENDING;
    }
}
