<?php

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Models\Message;
use App\Models\User;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\Route;

test('the payload separates gateway routing from the client frame', function () {
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

    expect($payload)->toHaveKeys(['gateway', 'client'])
        ->and($payload['gateway'])->toBe(['route' => ['server_id' => 12]])
        ->and(array_keys($payload['client']))->toBe(['op', 'data'])
        ->and($payload['client']['op'])->toBe(BroadcastOperation::MESSAGE_CREATED->value)
        ->and($payload['client']['data']['id'])->toBe(56)
        ->and($payload['client']['data']['server_id'])->toBe(12)
        ->and($payload['client']['data']['channel_id'])->toBe(34)
        ->and($payload['client']['data']['user_id'])->toBe(78);
});

test('every id the client needs is inside data', function () {
    $payload = json_decode(
        json_encode(
            GatewayEvent::channelDeleted(channelId: 56, serverId: 12, type: ChannelType::Text),
            JSON_THROW_ON_ERROR,
        ),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($payload)->toBe([
        'gateway' => ['route' => ['server_id' => 12]],
        'client' => [
            'op' => BroadcastOperation::CHANNEL_DELETED->value,
            'data' => [
                'id' => 56,
                'server_id' => 12,
                'type' => ChannelType::Text->value,
            ],
        ],
    ]);
});

test('a route without a server id is rejected instead of silently dropped by the gateway', function () {
    Route::server(0);
})->throws(InvalidArgumentException::class);

test('a user route targets explicit users without a server', function () {
    expect(json_encode(Route::users(3, 9, 3), JSON_THROW_ON_ERROR))->toBe('{"user_ids":[3,9]}');
});

test('a user route without valid user ids is rejected', function (array $userIds) {
    Route::users(...$userIds);
})->with([[[]], [[0]], [[3, -1]]])->throws(InvalidArgumentException::class);
