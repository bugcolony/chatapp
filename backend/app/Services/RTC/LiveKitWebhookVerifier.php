<?php

namespace App\Services\RTC;

use Agence104\LiveKit\WebhookReceiver;
use App\Exceptions\InvalidWebhookSignature;
use Exception;
use Livekit\WebhookEvent;

class LiveKitWebhookVerifier
{
    public function __construct(private WebhookReceiver $receiver) {}

    /**
     * @throws InvalidWebhookSignature
     */
    public function verify(string $rawBody, string $authHeader): WebhookEvent
    {
        try {
            return $this->receiver->receive($rawBody, $authHeader);
        } catch (Exception $e) {
            throw new InvalidWebhookSignature($e->getMessage(), previous: $e);
        }
    }
}
