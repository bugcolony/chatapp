<?php

namespace App\Actions\Channel;

use App\Enums\ChannelType;
use App\Events\ChannelDeleted;
use App\Models\Channel;
use Illuminate\Support\Facades\DB;

class DeleteChannel
{
    public function execute(Channel $channel): void
    {
        DB::transaction(function () use ($channel): void {
            if ($channel->type === ChannelType::Category) {
                $channel->children()->update(['parent_id' => null]);
            }

            $channel->delete();
        });

        ChannelDeleted::dispatch(
            channelId: $channel->id,
            serverId: $channel->server_id,
            type: $channel->type,
        );
    }
}
