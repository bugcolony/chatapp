<?php

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Models\Message;
use App\Models\User;
use App\Services\Gateway\BroadcastTarget;
use App\Services\Gateway\GatewayEvent;

test('the encoded envelope keeps the wire key names both consumers rely on', function () {
    $message = new Message([
        'server_id' => 12,
        'channel_id' => 34,
        'user_id' => 78,
        'content' => 'hello',
    ]);
    $message->id = 56;
    $message->created_at = now();
    $message->setRelation('author', new User(['name' => 'ada']));
    $message->setRelation('mentions', collect());
    $message->setRelation('attachment', null);

    $payload = json_decode(
        json_encode(GatewayEvent::messageCreated($message), JSON_THROW_ON_ERROR),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($payload['op'])->toBe(BroadcastOperation::MESSAGE_CREATED->value)
        ->and($payload['targetServerId'])->toBe(12)
        ->and($payload['targetChannelId'])->toBe(34)
        ->and($payload['senderId'])->toBe(78)
        ->and($payload['data']['id'])->toBe(56);
});

test('server scoped operations omit the browser only routing fields', function () {
    $payload = json_decode(
        json_encode(
            GatewayEvent::channelDeleted(channelId: 56, serverId: 12, type: ChannelType::Text),
            JSON_THROW_ON_ERROR,
        ),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($payload)->toBe([
        'op' => BroadcastOperation::CHANNEL_DELETED->value,
        'targetServerId' => 12,
        'data' => [
            'id' => 56,
            'type' => ChannelType::Text->value,
        ],
    ]);
});

test('a target without a server id is rejected instead of silently dropped by the gateway', function () {
    BroadcastTarget::server(0);
})->throws(InvalidArgumentException::class);
