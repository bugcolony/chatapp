<?php

namespace App\Policies;

use App\Enums\AppPermission;
use App\Models\Channel;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\Permissions\ServerPermissionContext;
use Throwable;

class MessagePolicy
{
    public function __construct()
    {
        //
    }

    /**
     * @throws Throwable
     */
    public function store(User $user, Channel $channel, int $attachmentBytes = 0): bool
    {
        if ($channel->server === null) {
            return false;
        }

        $ctx = ServerPermissionContext::for($user, $channel->server);

        if (! $ctx->resolveChannel($channel)->can(AppPermission::SEND_MESSAGES)) {
            return false;
        }

        if ($attachmentBytes === 0) {
            return true;
        }

        $usedBytes = MessageAttachment::query()
            ->join('messages', 'messages.id', '=', 'message_attachments.message_id')
            ->join('files', 'files.id', '=', 'message_attachments.file_id')
            ->where('messages.user_id', $user->id)
            ->sum('files.size');

        return (int) $usedBytes + $attachmentBytes <= 10_000_000;
    }

    public function view(User $user, Message $message): bool
    {
        if ($message->server === null) {
            return false;
        }

        try {
            $ctx = ServerPermissionContext::for($user, $message->server);

            return $ctx->resolveChannel($message->channel)->can(AppPermission::VIEW_CHANNELS);
        } catch (Throwable) {
            return false;
        }
    }
}
