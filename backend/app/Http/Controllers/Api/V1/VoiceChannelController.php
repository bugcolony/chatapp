<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ChannelType;
use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\RTC\LiveKitAccessService;
use Exception;

class VoiceChannelController extends Controller
{
    /**
     * @throws Exception
     */
    public function __invoke(Channel $channel, LiveKitAccessService $service)
    {
        if (!in_array($channel->type, [ChannelType::VOICE, ChannelType::DIRECT_MESSAGE, ChannelType::GROUP_DM], true)) {
            abort(404);
        }

        abort_if($channel->type === ChannelType::DIRECT_MESSAGE && ! $channel->hasFriendOf(auth()->user()), 403);

        abort_if(
            $channel->type === ChannelType::GROUP_DM
            && ! $channel->participants()->where('user_id', auth()->user()->id)->exists(),
            403
        );

        return response()->json([
            'token' => $service->newAccessToken($channel),
        ]);
    }
}
