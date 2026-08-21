<?php

namespace App\Actions\Channel;

use App\Enums\ChannelType;
use App\Events\ChannelDeleted;
use App\Models\Channel;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteChannel
{
    /**
     * @throws Throwable
     */
    public function execute(Channel $channel): void
    {
        DB::transaction(static function () use ($channel): void {
            if ($channel->type === ChannelType::Category) {
                $channel->children()->update(['parent_id' => null]);
            }

            if ($channel->type === ChannelType::Voice) {
                $channel->voiceTextChannel()->delete();
            }

            $channel->delete();
        });

        // TODO: Close livekit room

        ChannelDeleted::dispatch(
            channelId: $channel->id,
            serverId: $channel->server_id,
            type: $channel->type,
        );
    }
}
