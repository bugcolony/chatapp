<?php

use App\Events\ChannelDeleted;
use App\Models\Channel;
use App\Models\Member;
use App\Models\Message;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('deleting a category ungroups its children and dispatches one fan-out event', function () {
    Event::fake([ChannelDeleted::class]);

    $user = User::factory()->create();
    $server = Server::factory()->for($user, 'owner')->create();
    $category = Channel::factory()->for($server)->category()->create();
    $child = Channel::factory()->for($server)->create(['parent_id' => $category->id]);

    Member::factory()->for($user)->for($server)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/channels/{$category->id}")
        ->assertNoContent();

    expect($category->fresh()->trashed())->toBeTrue()
        ->and($child->fresh()->parent_id)->toBeNull();

    Event::assertDispatched(
        ChannelDeleted::class,
        fn (ChannelDeleted $event) => $event->channelId === $category->id
            && $event->serverId === $server->id
            && $event->type === $category->type,
    );
    Event::assertDispatchedTimes(ChannelDeleted::class, 1);
});

test('soft deleting a channel retains its messages', function () {
    Event::fake([ChannelDeleted::class]);

    $user = User::factory()->create();
    $server = Server::factory()->for($user, 'owner')->create();
    $channel = Channel::factory()->for($server)->create();
    $message = Message::factory()->inChannel($channel)->from($user)->create();

    Member::factory()->for($user)->for($server)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/channels/{$channel->id}")
        ->assertNoContent();

    expect($channel->fresh()->trashed())->toBeTrue()
        ->and($message->fresh())->not->toBeNull();
});
