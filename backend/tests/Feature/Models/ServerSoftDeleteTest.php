<?php

use App\Models\Channel;
use App\Models\Member;
use App\Models\Message;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('deleting a server takes its channels with it', function () {
    $server = Server::factory()->create();
    $channel = Channel::factory()->create(['server_id' => $server->id]);

    $server->delete();

    expect(Channel::find($channel->id))->toBeNull()
        ->and(Channel::withTrashed()->find($channel->id)->deleted_at)->not->toBeNull();
});

test('restoring a server brings back only the channels it took down', function () {
    $server = Server::factory()->create();
    $kept = Channel::factory()->create(['server_id' => $server->id]);
    $alreadyGone = Channel::factory()->create(['server_id' => $server->id]);

    $alreadyGone->delete();

    $this->travel(1)->minutes();

    $server->delete();
    $server->restore();

    expect(Channel::find($kept->id))->not->toBeNull()
        ->and(Channel::find($alreadyGone->id))->toBeNull();
});

test('channel routes stop resolving once the server is deleted', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $user->id]);
    $channel = Channel::factory()->create(['server_id' => $server->id]);

    Member::factory()->create([
        'user_id' => $user->id,
        'server_id' => $server->id,
        'left_at' => null,
    ]);

    Message::factory()->create([
        'server_id' => $server->id,
        'channel_id' => $channel->id,
        'user_id' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/channels/{$channel->id}/messages")->assertOk();

    $server->delete();

    $this->getJson("/api/v1/channels/{$channel->id}/messages")->assertNotFound();
});
