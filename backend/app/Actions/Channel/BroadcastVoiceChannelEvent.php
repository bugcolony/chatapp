<?php

namespace App\Actions\Channel;

use App\Enums\ChannelType;
use App\Enums\LiveKitWebhookEvent;
use App\Exceptions\InvalidWebhookSignature;
use App\Models\Channel;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;
use App\Services\RTC\LiveKitWebhookVerifier;
use App\Services\RTC\VoiceChannelPresence;
use Illuminate\Support\Facades\Log;

class BroadcastVoiceChannelEvent
{
    private const string ROOM_PREFIX = 'channel:';

    public function __construct(
        private readonly LiveKitWebhookVerifier $verifier,
        private readonly VoiceChannelPresence   $presence,
        private readonly RealtimeTransport      $transport,
    ) {}

    /**
     * @throws InvalidWebhookSignature
     */
    public function execute(string $authHeader, string $rawBody): void
    {
        $event = $this->verifier->verify($rawBody, $authHeader);
        $eventType = LiveKitWebhookEvent::tryFrom($event->getEvent());

        if ($eventType === null) {
            return;
        }

        $channel = $this->resolveVoiceChannel($event->getRoom()?->getName() ?? '');

        if ($channel === null) {
            return;
        }

        if ($eventType === LiveKitWebhookEvent::ROOM_FINISHED) {
            $this->presence->clear($channel->id);

            $this->transport->publish(
                GatewayEvent::voiceChannelClosed($channel)
            );

            return;
        }

        $userId = (int) $event->getParticipant()?->getIdentity();

        if ($userId <= 0) {
            Log::warning('LiveKit webhook missing participant identity', [
                'event' => $eventType->value,
                'channel_id' => $channel->id,
            ]);

            return;
        }

        match ($eventType) {
            LiveKitWebhookEvent::PARTICIPANT_JOINED => $this->presence->join($channel->id, $userId),
            LiveKitWebhookEvent::PARTICIPANT_LEFT => $this->presence->leave($channel->id, $userId),
        };

        $this->transport->publish(match ($eventType) {
            LiveKitWebhookEvent::PARTICIPANT_JOINED => GatewayEvent::userJoinedVoiceChannel($channel, $userId),
            LiveKitWebhookEvent::PARTICIPANT_LEFT => GatewayEvent::userLeftVoiceChannel($channel, $userId),
        });
    }

    private function resolveVoiceChannel(string $roomName): ?Channel
    {
        if (! str_starts_with($roomName, self::ROOM_PREFIX)) {
            return null;
        }

        $channelId = substr($roomName, strlen(self::ROOM_PREFIX));

        if (! ctype_digit($channelId)) {
            return null;
        }

        return Channel::withTrashed()
            ->select('id', 'server_id', 'type')
            ->whereIn('type', [ChannelType::VOICE, ChannelType::DIRECT_MESSAGE])
            ->find((int) $channelId);
    }
}
