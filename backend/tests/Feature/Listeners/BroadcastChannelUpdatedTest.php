<?php

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Events\ChannelUpdated;
use App\Listeners\BroadcastChannelUpdated;
use App\Models\Channel;
use App\Services\Gateway\RealtimeTransport;
use Tests\Support\FakeTransport;

test('channel updated payload targets every client subscribed to the server', function () {
    $channel = new Channel([
        'server_id' => 12,
        'parent_id' => 34,
        'name' => 'announcements',
        'type' => ChannelType::Text,
    ]);
    $channel->id = 56;

    $transport = new FakeTransport;
    $this->app->instance(RealtimeTransport::class, $transport);

    $this->app->make(BroadcastChannelUpdated::class)->handle(new ChannelUpdated($channel));

    $operation = $transport->sole();

    expect($operation->op)->toBe(BroadcastOperation::CHANNEL_UPDATED)
        ->and($operation->target->serverId)->toBe(12)
        ->and($operation->data)->toBe([
            'id' => 56,
            'server_id' => 12,
            'parent_id' => 34,
            'type' => ChannelType::Text,
            'name' => 'announcements',
        ]);
});
