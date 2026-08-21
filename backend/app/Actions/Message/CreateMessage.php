<?php

namespace App\Actions\Message;

use App\Events\MessageCreated;
use App\Exceptions\ChannelDoesNotSupportMessages;
use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use App\Services\Message\MessageAttachmentStorage;
use App\Services\Message\MessageParser;
use App\Services\Message\ParserContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateMessage
{
    public function __construct(
        private MessageAttachmentStorage $attachmentStorage,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(
        Channel       $channel,
        User          $author,
        ?string       $content,
        ?UploadedFile $upload,
    ): Message
    {
        if (! $channel->type->supportsMessages()) {
            throw new ChannelDoesNotSupportMessages("channel with type {$channel->type->value} cant receive messages");
        }

        $storedAttachment = $upload
            ? $this->attachmentStorage->store($upload, $channel)
            : null;

        try {
            $message = DB::transaction(function () use (
                $author,
                $channel,
                $content,
                $storedAttachment,
            ): Message {
                $message = $channel->messages()->create([
                    'content' => $content,
                    'server_id' => $channel->server_id,
                    'user_id' => $author->id,
                ]);


                if ($content) {
                    $this->insertMentions($message);
                }

                if ($storedAttachment) {
                    $message->attachment()->create($storedAttachment->toArray());
                }

                return $message;
            });
        } catch (Throwable $exception) {
            if ($storedAttachment) {
                $this->attachmentStorage->delete($storedAttachment);
            }

            throw $exception;
        }

        $message->load(['attachment', 'author']);
        MessageCreated::dispatch($message);

        return $message;
    }

    public function insertMentions(Message $message): void
    {
        $created_at = now();

        $message
            ->mentions()
            ->insert(
                new MessageParser($message->content)
                    ->parseMentionsWithContext(new ParserContext(serverId: $message->server_id))->map(function (array $mention) use ($message, $created_at) {
                        return [...$mention,
                            'message_id' => $message->id,
                            'created_at' => $created_at,
                            'updated_at' => $created_at,
                        ];
                    })->toArray()
            );
    }
}
