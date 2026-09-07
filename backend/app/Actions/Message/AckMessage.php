<?php

namespace App\Actions\Message;

use App\Models\Channel;
use App\Models\ChannelRead;
use App\Models\Member;
use App\Models\Message;
use RuntimeException;

class AckMessage
{
    public function execute(Channel $channel, Message $message): void
    {
        $channelRead = ChannelRead::where([
            'channel_id' => $channel->id,
            'user_id' => auth()->user()->id,
        ])->first();

        if ($channelRead) {
            $channelRead->update([
                'last_read_id' => max($message->id, $channelRead->last_read_id)
            ]);

            return;
        }

        $membership = Member::where([
            'server_id' => $channel->server_id,
            'user_id' => auth()->user()->id,
        ])->first();

        if (!$membership) {
            throw new RuntimeException('Not a valid channel');
        }

        ChannelRead::create([
            'channel_id' => $channel->id,
            'user_id' => auth()->user()->id,
            'last_read_id' => max($membership->baseline_message_id, $message->id),
        ]);
    }
}
