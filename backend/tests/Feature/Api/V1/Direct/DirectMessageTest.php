<?php

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

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
