<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ChannelType;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\RTC\VoiceChannelPresence;
use Illuminate\Http\JsonResponse;
use Throwable;

class VoicePresenceController extends Controller
{
    public function __invoke(Server $server, VoiceChannelPresence $presence): JsonResponse
    {
        try {
            $channelIds = $server->channels()
                ->where('type', ChannelType::Voice)
                ->pluck('id')
                ->all();

            $channels = $channelIds === [] ? [] : $presence->snapshot(...$channelIds);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'Voice presence is temporarily unavailable'], 503);
        }

        return response()->json(['channels' => (object) $channels]);
    }
}
