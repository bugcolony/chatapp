<?php

namespace App\Services\Gateway;

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Http\Resources\Api\V1\MessageAttachmentResource;
use App\Http\Resources\Api\V1\MessageMentionResource;
use App\Models\Channel;
use App\Models\Message;
use JsonSerializable;

final readonly class GatewayEvent implements JsonSerializable
{
    public function __construct(
        public BroadcastOperation $op,
        public BroadcastTarget $target,
        public array $data
    ) {}

    public static function messageCreated(Message $message): self
    {
        return new self(
            BroadcastOperation::MESSAGE_CREATED,
            BroadcastTarget::channel($message->server_id, $message->channel_id)->sender($message->user_id),
            [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'channel_id' => $message->channel_id,
                'server_id' => $message->server_id,
                'mentions' => $message->mentions
                    ? MessageMentionResource::collection($message->mentions)->resolve()
                    : null,
                'author' => $message->author->name,
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
            BroadcastTarget::server($channel->server_id),
            [
                'id' => $channel->id,
                'server_id' => $channel->server_id,
                'parent_id' => $channel->parent_id,
                'message_channel_id' => $channel->message_channel_id,
                'type' => $channel->type,
                'name' => $channel->name,
            ],
        );
    }

    public static function channelUpdated(Channel $channel): self
    {
        return new self(
            BroadcastOperation::CHANNEL_UPDATED,
            BroadcastTarget::server($channel->server_id),
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
            BroadcastTarget::server($serverId),
            [
                'id' => $channelId,
                'type' => $type,
            ],
        );
    }

    public static function userJoinedVoiceChannel(int $channelId, int $serverId, int $userId): self
    {
        return new self(
            BroadcastOperation::USER_JOINED_VOICE,
            BroadcastTarget::channel($serverId, $channelId),
            [
                'id' => $userId,
            ]
        );
    }

    public static function userLeftVoiceChannel(int $channelId, int $serverId, int $userId): self
    {
        return new self(
            BroadcastOperation::USER_LEFT_VOICE,
            BroadcastTarget::channel($serverId, $channelId),
            [
                'id' => $userId,
            ]
        );
    }

    public static function voiceChannelClosed(int $channelId, int $serverId): self
    {
        return new self(
            BroadcastOperation::VOICE_CHANNEL_CLOSED,
            BroadcastTarget::channel($serverId, $channelId),
            []
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'op' => $this->op->value,
            ...$this->target->toArray(),
            'data' => $this->data,
        ];
    }
}
