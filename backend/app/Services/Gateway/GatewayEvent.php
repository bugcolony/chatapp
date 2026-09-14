<?php

namespace App\Services\Gateway;

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Http\Resources\Api\V1\MessageAttachmentResource;
use App\Http\Resources\Api\V1\MessageMentionResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Channel;
use App\Models\Message;
use JsonSerializable;

final readonly class GatewayEvent implements JsonSerializable
{
    public function __construct(
        public BroadcastOperation $op,
        public Route $route,
        public array $data
    ) {}

    public static function messageCreated(Message $message): self
    {
        return new self(
            BroadcastOperation::MESSAGE_CREATED,
            Route::server($message->server_id),
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

    public static function userJoinedVoiceChannel(int $channelId, int $serverId, int $userId): self
    {
        return new self(
            BroadcastOperation::VOICE_USER_JOINED,
            Route::server($serverId),
            [
                'server_id' => $serverId,
                'channel_id' => $channelId,
                'user_id' => $userId,
            ]
        );
    }

    public static function userLeftVoiceChannel(int $channelId, int $serverId, int $userId): self
    {
        return new self(
            BroadcastOperation::VOICE_USER_LEFT,
            Route::server($serverId),
            [
                'server_id' => $serverId,
                'channel_id' => $channelId,
                'user_id' => $userId,
            ]
        );
    }

    public static function voiceChannelClosed(int $channelId, int $serverId): self
    {
        return new self(
            BroadcastOperation::VOICE_CHANNEL_CLOSED,
            Route::server($serverId),
            [
                'server_id' => $serverId,
                'channel_id' => $channelId,
            ]
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'gateway' => ['route' => $this->route],
            'client' => ['op' => $this->op->value, 'data' => $this->data],
        ];
    }
}
