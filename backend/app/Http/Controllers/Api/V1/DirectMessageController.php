<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ChannelType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DirectMessageChannelResource;
use App\Models\Channel;
use App\Models\ChannelRead;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class DirectMessageController extends Controller
{
    /**
     * @throws Throwable
     */
    public function index(): ResourceCollection
    {
        $user = auth()->user();

        return DirectMessageChannelResource::collection($user
            ->directMessageChannels()
            ->withPivot('hidden_before_message_id')
            ->with('participants')
            ->select('channels.*')
            ->addSelect(['last_read_id' => ChannelRead::query()
                ->select('last_read_id')
                ->whereColumn('channel_reads.channel_id', 'channels.id')
                ->where('channel_reads.user_id', $user->id)])
            ->get());
    }

    public function open(User $friend): DirectMessageChannelResource
    {
        $user = auth()->user();

        abort_if($user->is($friend), 404);

        $channel = Channel::query()
            ->where('type', ChannelType::DIRECT_MESSAGE)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $friend->id))
            ->firstOrFail();

        $channel->participants()->updateExistingPivot($user->id, ['hidden_before_message_id' => null]);

        return DirectMessageChannelResource::make(
            $channel->load('participants')
        );
    }
}
