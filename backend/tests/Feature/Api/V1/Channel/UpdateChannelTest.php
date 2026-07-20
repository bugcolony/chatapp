<?php

use App\Events\ChannelUpdated;
use App\Models\Channel;
use App\Models\Member;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('updating a channel dispatches a server fan-out event', function () {
    Event::fake([ChannelUpdated::class]);

    $user = User::factory()->create();
    $server = Server::factory()->for($user, 'owner')->create();
    $category = Channel::factory()->for($server)->category()->create();
    $channel = Channel::factory()->for($server)->create();

    Member::factory()->for($user)->for($server)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/v1/channels/{$channel->id}", [
        'name' => 'announcements',
        'parent_id' => $category->id,
    ])->assertOk()
        ->assertJsonPath('data.name', 'announcements')
        ->assertJsonPath('data.parent_id', $category->id);

    Event::assertDispatched(
        ChannelUpdated::class,
        fn (ChannelUpdated $event) => $event->channel->is($channel)
            && $event->channel->name === 'announcements'
            && $event->channel->parent_id === $category->id,
    );
});
