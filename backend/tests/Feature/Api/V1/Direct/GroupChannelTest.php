<?php

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
});

function makeFriends(User $user, User $friend): void
{
    Sanctum::actingAs($user);
    test()->postJson('/api/v1/friends', ['username' => $friend->username])->assertCreated();

    Sanctum::actingAs($friend);
    test()->postJson("/api/v1/friends/{$user->id}/accept")->assertOk();
}

function makeGroup(User $owner, array $friends): int
{
    foreach ($friends as $friend) {
        makeFriends($owner, $friend);
    }

    Sanctum::actingAs($owner);

    return test()->postJson('/api/v1/direct/groups', [
        'participant_ids' => collect($friends)->pluck('id')->all(),
    ])->assertCreated()->json('data.id');
}

test('a group is created with the owner and the given friends', function () {
    [$alice, $bob, $carol] = User::factory()->count(3)->create();

    $channelId = makeGroup($alice, [$bob, $carol]);

    $channel = Channel::findOrFail($channelId);

    expect($channel->type)->toBe(ChannelType::GROUP_DM)
        ->and($channel->owner_id)->toBe($alice->id)
        ->and($channel->server_id)->toBeNull()
        ->and($channel->participants()->pluck('users.id')->sort()->values()->all())
        ->toBe(collect([$alice->id, $bob->id, $carol->id])->sort()->values()->all());
});

test('a group cannot be created with a non friend', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);

    $this->postJson('/api/v1/direct/groups', ['participant_ids' => [$bob->id]])
        ->assertStatus(422);
});

test('group participants can read and send messages', function () {
    [$alice, $bob, $carol] = User::factory()->count(3)->create();
    $channelId = makeGroup($alice, [$bob, $carol]);

    Sanctum::actingAs($bob);
    $this->postJson("/api/v1/channels/{$channelId}/messages", ['content' => 'hi', 'client_id' => 1])->assertCreated();

    Sanctum::actingAs($carol);
    $this->getJson("/api/v1/channels/{$channelId}/messages")->assertOk();
});

test('a non participant cannot read or send in a group', function () {
    [$alice, $bob, $carol, $dave] = User::factory()->count(4)->create();
    $channelId = makeGroup($alice, [$bob, $carol]);

    Sanctum::actingAs($dave);
    $this->getJson("/api/v1/channels/{$channelId}/messages")->assertForbidden();
    $this->postJson("/api/v1/channels/{$channelId}/messages", ['content' => 'hi', 'client_id' => 1])->assertForbidden();
});

test('a participant can add a friend and the newcomer does not get the backlog', function () {
    [$alice, $bob, $dave] = User::factory()->count(3)->create();
    $channelId = makeGroup($alice, [$bob]);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/channels/{$channelId}/messages", ['content' => 'before', 'client_id' => 1])->assertCreated();

    makeFriends($alice, $dave);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/direct/{$channelId}/participants", ['user_id' => $dave->id])
        ->assertOk()
        ->assertJsonCount(3, 'data.participants');

    $hidden = DB::table('channel_participants')
        ->where('channel_id', $channelId)
        ->where('user_id', $dave->id)
        ->value('hidden_before_message_id');

    expect((int) $hidden)->toBeGreaterThan(0);
});

test('only the owner can remove a participant', function () {
    [$alice, $bob, $carol] = User::factory()->count(3)->create();
    $channelId = makeGroup($alice, [$bob, $carol]);

    Sanctum::actingAs($bob);
    $this->deleteJson("/api/v1/direct/{$channelId}/participants/{$carol->id}")->assertForbidden();

    Sanctum::actingAs($alice);
    $this->deleteJson("/api/v1/direct/{$channelId}/participants/{$carol->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data.participants');
});

test('removing someone who is not in the group is not found', function () {
    [$alice, $bob, $dave] = User::factory()->count(3)->create();
    $channelId = makeGroup($alice, [$bob]);

    Sanctum::actingAs($alice);
    $this->deleteJson("/api/v1/direct/{$channelId}/participants/{$dave->id}")->assertNotFound();
});

test('the owner cannot be removed', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channelId = makeGroup($alice, [$bob]);

    Sanctum::actingAs($alice);
    $this->deleteJson("/api/v1/direct/{$channelId}/participants/{$alice->id}")->assertStatus(422);
});

test('leaving hands ownership over and drops the participant row', function () {
    [$alice, $bob, $carol] = User::factory()->count(3)->create();
    $channelId = makeGroup($alice, [$bob, $carol]);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/direct/{$channelId}/leave")->assertNoContent();

    $channel = Channel::findOrFail($channelId);

    expect($channel->participants()->where('user_id', $alice->id)->exists())->toBeFalse()
        ->and($channel->owner_id)->not->toBe($alice->id);

    Sanctum::actingAs($alice);
    $this->getJson("/api/v1/channels/{$channelId}/messages")->assertForbidden();
});

test('the last participant leaving deletes the group', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channelId = makeGroup($alice, [$bob]);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/direct/{$channelId}/leave")->assertNoContent();

    Sanctum::actingAs($bob);
    $this->postJson("/api/v1/direct/{$channelId}/leave")->assertNoContent();

    expect(Channel::find($channelId))->toBeNull();
});

test('opening a direct channel never returns a group', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    makeFriends($alice, $bob);

    Sanctum::actingAs($alice);
    $groupId = $this->postJson('/api/v1/direct/groups', ['participant_ids' => [$bob->id]])
        ->assertCreated()->json('data.id');

    $this->postJson("/api/v1/direct/{$bob->id}")
        ->assertOk()
        ->assertJsonPath('data.type', ChannelType::DIRECT_MESSAGE->value);

    expect($this->postJson("/api/v1/direct/{$bob->id}")->json('data.id'))->not->toBe($groupId);
});

test('a mention of a group participant is recorded', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channelId = makeGroup($alice, [$bob]);

    Sanctum::actingAs($alice);
    $messageId = $this->postJson("/api/v1/channels/{$channelId}/messages", [
        'content' => "hey [@{$bob->id}] look",
        'client_id' => 1,
    ])->assertCreated()->json('data.id');

    expect(DB::table('message_mentions')->where('message_id', $messageId)->pluck('user_id')->all())
        ->toBe([$bob->id]);
});

test('a fetched message carries its mention names', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channelId = makeGroup($alice, [$bob]);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/channels/{$channelId}/messages", [
        'content' => "hey [@{$bob->id}]",
        'client_id' => 1,
    ])->assertCreated();

    $this->getJson("/api/v1/channels/{$channelId}/messages")
        ->assertOk()
        ->assertJsonPath('data.0.mentions.0.user_id', $bob->id)
        ->assertJsonPath('data.0.mentions.0.fallback_name', $bob->name);
});

test('a mention of a non participant is dropped', function () {
    [$alice, $bob, $dave] = User::factory()->count(3)->create();
    $channelId = makeGroup($alice, [$bob]);

    Sanctum::actingAs($alice);
    $messageId = $this->postJson("/api/v1/channels/{$channelId}/messages", [
        'content' => "hey [@{$dave->id}]",
        'client_id' => 1,
    ])->assertCreated()->json('data.id');

    expect(DB::table('message_mentions')->where('message_id', $messageId)->count())->toBe(0);
});

test('a group appears in the direct channel list for its participants', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channelId = makeGroup($alice, [$bob]);

    Sanctum::actingAs($bob);
    $this->getJson('/api/v1/direct')
        ->assertOk()
        ->assertJsonFragment(['id' => $channelId, 'type' => ChannelType::GROUP_DM->value]);
});

test('a group cannot be edited or deleted through the generic channel endpoint', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channelId = makeGroup($alice, [$bob]);

    Sanctum::actingAs($alice);
    $this->patchJson("/api/v1/channels/{$channelId}", ['name' => 'hijacked'])->assertForbidden();
    $this->deleteJson("/api/v1/channels/{$channelId}")->assertForbidden();

    expect(Channel::find($channelId))->not->toBeNull();
});

test('a group channel cannot be created through the server channel endpoint', function () {
    $user = User::factory()->create();
    $server = App\Models\Server::factory()->create();
    $server->members()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/servers/{$server->id}/channels", [
        'name' => 'sneaky',
        'type' => ChannelType::GROUP_DM->value,
    ])->assertStatus(422);
});
