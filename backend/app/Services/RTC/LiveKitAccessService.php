<?php

namespace App\Services\RTC;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Models\Channel;
use Exception;

class LiveKitAccessService
{
    /**
     * @throws Exception
     */
    public function newAccessToken(Channel $channel): string
    {
        $room = "channel:$channel->id";
        $userIdentifier = auth()->id();

        $tokenOptions = new AccessTokenOptions()->setIdentity($userIdentifier);

        $videoGrant = new VideoGrant()->setRoomJoin()->setRoomName($room);

        return new AccessToken(
            config('services.rtc.livekit.api_key'),
            config('services.rtc.livekit.secret')
        )->init($tokenOptions)->setGrant($videoGrant)->toJwt();
    }
}
