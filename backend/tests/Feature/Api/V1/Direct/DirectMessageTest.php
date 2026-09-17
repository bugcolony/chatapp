<?php

use App\Enums\ChannelType;
use App\Enums\FriendStatus;
use App\Models\Channel;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
});

function befriend(User $user, User $friend): Channel
{
    Sanctum::actingAs($user);
    test()->postJson('/api/v1/friends', ['username' => $friend->username])->assertCreated();

    Sanctum::actingAs($friend);
    test()->postJson("/api/v1/friends/{$user->id}/accept")->assertOk();

    return Channel::query()
        ->where('type', ChannelType::DIRECT_MESSAGE)
        ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
        ->whereHas('participants', fn ($query) => $query->where('user_id', $friend->id))
        ->sole();
}

test('a direct channel created on accept is hidden from both users', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    befriend($alice, $bob);

    Sanctum::actingAs($alice);
    $this->getJson('/api/v1/direct')->assertOk()->assertJsonCount(0, 'data');

    Sanctum::actingAs($bob);
    $this->getJson('/api/v1/direct')->assertOk()->assertJsonCount(0, 'data');
});

test('opening a direct channel shows it only for the user who opened it', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channel = befriend($alice, $bob);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/direct/{$bob->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $channel->id)
        ->assertJsonCount(2, 'data.participants')
        ->assertJsonFragment(['id' => $alice->id])
        ->assertJsonFragment(['id' => $bob->id]);

    $this->getJson('/api/v1/direct')->assertJsonPath('data.0.id', $channel->id);

    Sanctum::actingAs($bob);
    $this->getJson('/api/v1/direct')->assertJsonCount(0, 'data');
});

test('a hidden direct channel reappears once a newer message exists', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channel = befriend($alice, $bob);

    DB::table('channels')->where('id', $channel->id)->update(['last_message_id' => 5]);

    Sanctum::actingAs($bob);
    $this->getJson('/api/v1/direct')->assertJsonPath('data.0.id', $channel->id);
});

test('the list never includes direct channels of other users', function () {
    [$alice, $bob, $carol] = User::factory()->count(3)->create();
    $foreign = befriend($bob, $carol);
    DB::table('channels')->where('id', $foreign->id)->update(['last_message_id' => 5]);

    Sanctum::actingAs($alice);
    $this->getJson('/api/v1/direct')->assertOk()->assertJsonCount(0, 'data');
});

test('opening without a shared direct channel is not found', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/direct/{$bob->id}")->assertNotFound();
});

test('opening a direct channel with yourself is not found', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    befriend($alice, $bob);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/direct/{$alice->id}")->assertNotFound();
});

test('the list exposes read state for each direct channel', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channel = befriend($alice, $bob);

    DB::table('channels')->where('id', $channel->id)->update(['last_message_id' => 7]);
    DB::table('channel_reads')->insert([
        'channel_id' => $channel->id,
        'user_id' => $alice->id,
        'last_read_id' => 4,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Sanctum::actingAs($alice);
    $this->getJson('/api/v1/direct')
        ->assertOk()
        ->assertJsonPath('data.0.last_message_id', 7)
        ->assertJsonPath('data.0.last_read_id', 4);
});

test('friends can message and call in their direct channel', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channel = befriend($alice, $bob);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/channels/{$channel->id}/messages", ['content' => 'hi', 'client_id' => 1])->assertSuccessful();
    $this->postJson("/api/v1/channels/{$channel->id}/credentials")->assertOk();
});

test('unfriending locks the direct channel for both users', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channel = befriend($alice, $bob);

    Sanctum::actingAs($alice);
    $this->deleteJson("/api/v1/friends/{$bob->id}")->assertOk();

    foreach ([$alice, $bob] as $user) {
        Sanctum::actingAs($user);
        $this->postJson("/api/v1/channels/{$channel->id}/messages", ['content' => 'hi', 'client_id' => 1])->assertForbidden();
        $this->postJson("/api/v1/channels/{$channel->id}/credentials")->assertForbidden();
        $this->getJson("/api/v1/channels/{$channel->id}/messages")->assertOk();
    }
});

test('blocking locks the direct channel for both users', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channel = befriend($alice, $bob);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/friends/{$bob->id}/block")->assertOk();

    foreach ([$alice, $bob] as $user) {
        Sanctum::actingAs($user);
        $this->postJson("/api/v1/channels/{$channel->id}/messages", ['content' => 'hi', 'client_id' => 1])->assertForbidden();
        $this->postJson("/api/v1/channels/{$channel->id}/credentials")->assertForbidden();
    }
});

test('unblocking leaves the users unfriended', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    befriend($alice, $bob);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/friends/{$bob->id}/block")->assertOk();
    $this->deleteJson("/api/v1/friends/{$bob->id}/block")->assertOk();
    $this->deleteJson("/api/v1/friends/{$bob->id}/block")->assertNotFound();

    expect(Friend::query()->count())->toBe(0);
});

test('unblocking does not lift the other side\'s block', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    Friend::create(['user_id' => $bob->id, 'friend_id' => $alice->id, 'status' => FriendStatus::BLOCKED]);

    Sanctum::actingAs($alice);
    $this->deleteJson("/api/v1/friends/{$bob->id}/block")->assertNotFound();

    expect(Friend::query()->between($bob->id, $alice->id)->value('status'))->toBe(FriendStatus::BLOCKED);
});

test('befriending again unlocks the same direct channel', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    $channel = befriend($alice, $bob);

    Sanctum::actingAs($alice);
    $this->deleteJson("/api/v1/friends/{$bob->id}")->assertOk();

    expect(befriend($alice, $bob)->id)->toBe($channel->id);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/channels/{$channel->id}/messages", ['content' => 'hi', 'client_id' => 1])->assertSuccessful();
});
