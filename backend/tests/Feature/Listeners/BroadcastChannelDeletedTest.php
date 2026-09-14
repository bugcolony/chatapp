<?php

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Events\ChannelDeleted;
use App\Listeners\BroadcastChannelDeleted;
use App\Services\Gateway\RealtimeTransport;
use Tests\Support\FakeTransport;

test('channel deleted payload targets every client subscribed to the server', function () {
    $transport = new FakeTransport;
    $this->app->instance(RealtimeTransport::class, $transport);

    $this->app->make(BroadcastChannelDeleted::class)->handle(new ChannelDeleted(
        channelId: 56,
        serverId: 12,
        type: ChannelType::Text,
    ));

    $operation = $transport->sole();

    expect($operation->op)->toBe(BroadcastOperation::CHANNEL_DELETED)
        ->and($operation->route->serverId)->toBe(12)
        ->and($operation->data)->toBe([
            'id' => 56,
            'server_id' => 12,
            'type' => ChannelType::Text,
        ]);
});
