<?php

namespace App\Actions\Friend;

use App\Enums\FriendStatus;
use App\Models\Friend;
use App\Models\User;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class SendFriendRequest
{
    public function __construct(private RealtimeTransport $transport) {}

    /**
     * @throws Throwable
     */
    public function execute(User $user, User $friend): FriendStatus
    {
        $existing = Friend::query()->between($user->id, $friend->id)->first();

        if ($existing?->status === FriendStatus::INCOMING_PENDING) {
            app(AcceptFriendRequest::class)->execute($user, $friend);

            return FriendStatus::FRIEND;
        }

        $blockedByFriend = Friend::query()
            ->between($friend->id, $user->id)
            ->where('status', FriendStatus::BLOCKED)
            ->exists();

        if ($blockedByFriend) {
            throw ValidationException::withMessages(['username' => "Couldn't send a friend request to that user."]);
        }

        if ($existing && $existing->status !== FriendStatus::BLOCKED) {
            throw ValidationException::withMessages([
                'username' => $existing->status === FriendStatus::FRIEND
                    ? "You're already friends."
                    : 'Friend request already sent.',
            ]);
        }

        $now = now();

        try {
            DB::transaction(static function () use ($user, $friend, $existing, $now): void {
                $existing?->delete();

                Friend::query()->insert([
                    ['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => FriendStatus::OUTGOING_PENDING->value, 'created_at' => $now, 'updated_at' => $now],
                    ['user_id' => $friend->id, 'friend_id' => $user->id, 'status' => FriendStatus::INCOMING_PENDING->value, 'created_at' => $now, 'updated_at' => $now],
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            return $this->execute($user, $friend);
        }

        $this->transport->publish(GatewayEvent::friendRequestReceived($user, $friend->id));

        return FriendStatus::OUTGOING_PENDING;
    }
}
