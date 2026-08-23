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
        if ($channel->type !== ChannelType::Voice) {
            abort(404);
        }

        return response()->json([
            'token' => $service->newAccessToken($channel),
        ]);
    }
}
