<?php

namespace App\Listeners;

use App\Enums\BroadcastOperation;
use App\Events\MessageCreated;
use App\Http\Resources\Api\V1\MessageAttachmentResource;
use App\Http\Resources\Api\V1\MessageMentionResource;
use Illuminate\Support\Facades\Redis;
use JsonException;

class BroadcastMessage
{
    public function __construct()
    {
        //
    }

    /**
     * @throws JsonException
     */
    public function handle(MessageCreated $event): void
    {
        $message = $event->message;

        Redis::connection('realtime')->client()->publish(
            'messages.created',
            json_encode([
                'op' => BroadcastOperation::MESSAGE_CREATED,
                'targetChannelId' => $message->channel_id,
                'targetServerId' => $message->server_id,
                'senderId' => $message->user_id,
                'data' => [
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
                ]], JSON_THROW_ON_ERROR)
        );
    }
}
