<?php

use App\Enums\ChannelType;
use App\Events\ChannelCreated;
use App\Models\Channel;
use App\Models\Member;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('creating a channel dispatches a server fan-out event', function () {
    Event::fake([ChannelCreated::class]);

    $user = User::factory()->create();
    $server = Server::factory()->for($user, 'owner')->create();

    Member::factory()->for($user)->for($server)->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/servers/{$server->id}/channels", [
        'name' => 'announcements',
        'type' => ChannelType::Text->value,
    ])->assertCreated();

    $channel = Channel::query()->sole();

    Event::assertDispatched(
        ChannelCreated::class,
        fn (ChannelCreated $event) => $event->channel->is($channel),
    );
});
