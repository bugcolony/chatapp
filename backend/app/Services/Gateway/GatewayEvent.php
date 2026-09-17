<?php

namespace App\Services\Gateway;

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Http\Resources\Api\V1\FriendResource;
use App\Http\Resources\Api\V1\MessageAttachmentResource;
use App\Http\Resources\Api\V1\MessageMentionResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use JsonSerializable;

final readonly class GatewayEvent implements JsonSerializable
{
    public function __construct(
        public BroadcastOperation $op,
        public Route              $route,
        public array              $data,
        public ?BroadcastOperation $gatewayOp = null,
    )
    {
    }

    public static function messageCreated(Message $message, Channel $channel): self
    {
        return new self(
            BroadcastOperation::MESSAGE_CREATED,
            self::channelRoute($channel),
            [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'channel_id' => $message->channel_id,
                'server_id' => $message->server_id,
                'mentions' => $message->mentions
                    ? MessageMentionResource::collection($message->mentions)->resolve()
                    : null,
                'author' => UserResource::make($message->author)->resolve(),
                'message' => $message->content,
                'attachment' => $message->attachment
                    ? MessageAttachmentResource::make($message->attachment)->resolve()
                    : null,
                'created_at' => $message->created_at,
            ],
        );
    }

    public static function channelCreated(Channel $channel): self
    {
        return new self(
            BroadcastOperation::CHANNEL_CREATED,
            Route::server($channel->server_id),
            [
                'id' => $channel->id,
                'server_id' => $channel->server_id,
                'parent_id' => $channel->parent_id,
                'type' => $channel->type,
                'name' => $channel->name,
            ],
        );
    }

    public static function channelUpdated(Channel $channel): self
    {
        return new self(
            BroadcastOperation::CHANNEL_UPDATED,
            Route::server($channel->server_id),
            [
                'id' => $channel->id,
                'server_id' => $channel->server_id,
                'parent_id' => $channel->parent_id,
                'type' => $channel->type,
                'name' => $channel->name,
            ],
        );
    }

    public static function channelDeleted(int $channelId, int $serverId, ChannelType $type): self
    {
        return new self(
            BroadcastOperation::CHANNEL_DELETED,
            Route::server($serverId),
            [
                'id' => $channelId,
                'server_id' => $serverId,
                'type' => $type,
            ],
        );
    }

    public static function userJoinedVoiceChannel(Channel $channel, int $userId): self
    {
        return new self(
            BroadcastOperation::VOICE_USER_JOINED,
            self::channelRoute($channel),
            [
                'server_id' => $channel->server_id,
                'channel_id' => $channel->id,
                'user_id' => $userId,
            ]
        );
    }

    public static function userLeftVoiceChannel(Channel $channel, int $userId): self
    {
        return new self(
            BroadcastOperation::VOICE_USER_LEFT,
            self::channelRoute($channel),
            [
                'server_id' => $channel->server_id,
                'channel_id' => $channel->id,
                'user_id' => $userId,
            ]
        );
    }

    public static function voiceChannelClosed(Channel $channel): self
    {
        return new self(
            BroadcastOperation::VOICE_CHANNEL_CLOSED,
            self::channelRoute($channel),
            [
                'server_id' => $channel->server_id,
                'channel_id' => $channel->id,
            ]
        );
    }

    public static function friendRequestReceived(User $sender, int $recipientId): self
    {
        return new self(
            BroadcastOperation::FRIEND_REQUEST_RECEIVED,
            Route::users($recipientId),
            [
                'user' => FriendResource::make($sender)->resolve(),
            ],
        );
    }

    public static function friendAdded(User $user, User $friend, int $channelId): self
    {
        return new self(
            BroadcastOperation::FRIEND_ADDED,
            Route::users($user->id, $friend->id),
            [
                'channel_id' => $channelId,
                'users' => [
                    FriendResource::make($user)->resolve(),
                    FriendResource::make($friend)->resolve(),
                ],
            ],
            BroadcastOperation::FRIEND_ADDED,
        );
    }

    public static function friendRemoved(int $userId, int $friendId): self
    {
        return new self(
            BroadcastOperation::FRIEND_REMOVED,
            Route::users($userId, $friendId),
            [
                'user_ids' => [$userId, $friendId],
            ],
            BroadcastOperation::FRIEND_REMOVED,
        );
    }

    private static function channelRoute(Channel $channel): Route
    {
        return $channel->type === ChannelType::DIRECT_MESSAGE
            ? Route::users(...DB::table('channel_participants')
                ->where('channel_id', $channel->id)
                ->orderBy('user_id')
                ->pluck('user_id')
                ->all())
            : Route::server($channel->server_id);
    }

    public function jsonSerialize(): array
    {
        $gateway = ['route' => $this->route];

        if ($this->gatewayOp !== null) {
            $gateway['op'] = $this->gatewayOp->value;
        }

        return [
            'gateway' => $gateway,
            'client' => ['op' => $this->op->value, 'data' => $this->data],
        ];
    }
}
