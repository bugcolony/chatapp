<?php

namespace App\Actions\Message;

use App\Models\Channel;
use App\Models\Member;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AckMessage
{
    public function execute(Channel $channel, Message $message): void
    {
        $userId = auth()->user()->id;

        $membership = Member::query()
            ->where('server_id', $channel->server_id)
            ->where('user_id', $userId)
            ->first();

        if (!$membership) {
            throw new RuntimeException('Not a valid channel');
        }

        DB::statement(<<<'SQL'
            insert into channel_reads (channel_id, user_id, last_read_id, created_at, updated_at)
            values (?, ?, greatest(?::bigint, ?::bigint), now(), now())
            on conflict (user_id, channel_id) do update
            set last_read_id = greatest(channel_reads.last_read_id, ?::bigint),
                updated_at = now()
        SQL, [
            $channel->id,
            $userId,
            $membership->baseline_message_id,
            $message->id,
            $message->id,
        ]);
    }
}
