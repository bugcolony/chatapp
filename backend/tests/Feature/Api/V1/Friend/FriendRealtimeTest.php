<?php

use App\Enums\BroadcastOperation;
use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\User;
use App\Services\Gateway\RealtimeTransport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeTransport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->transport = new FakeTransport;
    $this->app->instance(RealtimeTransport::class, $this->transport);
});

test('sending a request notifies only the recipient', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    Sanctum::actingAs($alice);

    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    $event = $this->transport->sole();

    expect($event->op)->toBe(BroadcastOperation::FRIEND_REQUEST_RECEIVED)
        ->and($event->route->userIds)->toBe([$bob->id])
        ->and($event->gatewayOp)->toBeNull()
        ->and($event->data['user']['id'])->toBe($alice->id);
});

test('accepting notifies both users and tells the gateway to link them', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    Sanctum::actingAs($bob);
    $this->postJson("/api/v1/friends/{$alice->id}/accept")->assertOk();

    $channel = Channel::query()->where('type', ChannelType::DIRECT_MESSAGE)->sole();
    $event = collect($this->transport->published)->last();

    expect($event->op)->toBe(BroadcastOperation::FRIEND_ADDED)
        ->and($event->gatewayOp)->toBe(BroadcastOperation::FRIEND_ADDED)
        ->and($event->route->userIds)->toBe([$bob->id, $alice->id])
        ->and($event->data['channel_id'])->toBe($channel->id)
        ->and(collect($event->data['users'])->pluck('id')->all())->toBe([$bob->id, $alice->id]);
});

test('unfriending notifies both users and tells the gateway to unlink them', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    Sanctum::actingAs($bob);
    $this->postJson("/api/v1/friends/{$alice->id}/accept")->assertOk();
    $this->deleteJson("/api/v1/friends/{$alice->id}")->assertOk();

    $event = collect($this->transport->published)->last();

    expect($event->op)->toBe(BroadcastOperation::FRIEND_REMOVED)
        ->and($event->gatewayOp)->toBe(BroadcastOperation::FRIEND_REMOVED)
        ->and($event->route->userIds)->toBe([$bob->id, $alice->id])
        ->and($event->data['user_ids'])->toBe([$bob->id, $alice->id]);
});
