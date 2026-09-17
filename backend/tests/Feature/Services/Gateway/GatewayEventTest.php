<?php

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\Message;
use App\Models\Server;
use App\Models\User;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

    $channel = new Channel(['server_id' => 12, 'type' => ChannelType::TEXT]);
    $channel->id = 34;

    $payload = json_decode(
        json_encode(GatewayEvent::messageCreated($message, $channel), JSON_THROW_ON_ERROR),
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
            GatewayEvent::channelDeleted(channelId: 56, serverId: 12, type: ChannelType::TEXT),
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
                'type' => ChannelType::TEXT->value,
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

test('voice events in a server channel are routed to the server', function () {
    $server = Server::factory()->for(User::factory(), 'owner')->create();
    $channel = Channel::factory()->for($server)->voice()->create();

    expect(GatewayEvent::userJoinedVoiceChannel($channel, 78)->route->serverId)->toBe($server->id)
        ->and(GatewayEvent::userLeftVoiceChannel($channel, 78)->route->userIds)->toBe([])
        ->and(GatewayEvent::voiceChannelClosed($channel)->route->serverId)->toBe($server->id)
        ->and(GatewayEvent::voiceChannelClosed($channel)->data)->toBe([
            'server_id' => $server->id,
            'channel_id' => $channel->id,
        ]);
});

test('voice events in a direct channel are routed to its participants', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channel = Channel::create(['type' => ChannelType::DIRECT_MESSAGE]);
    $channel->participants()->attach([$alice->id, $bob->id]);

    $joined = GatewayEvent::userJoinedVoiceChannel($channel, $alice->id);

    expect($joined->route->serverId)->toBeNull()
        ->and($joined->route->userIds)->toBe([$alice->id, $bob->id])
        ->and($joined->data)->toBe([
            'server_id' => null,
            'channel_id' => $channel->id,
            'user_id' => $alice->id,
        ])
        ->and(GatewayEvent::userLeftVoiceChannel($channel, $alice->id)->route->userIds)->toBe([$alice->id, $bob->id])
        ->and(GatewayEvent::voiceChannelClosed($channel)->route->userIds)->toBe([$alice->id, $bob->id]);
});
