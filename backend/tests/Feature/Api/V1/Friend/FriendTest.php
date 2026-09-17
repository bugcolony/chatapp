<?php

use App\Enums\FriendStatus;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function friendStatus(User $user, User $friend): ?FriendStatus
{
    return Friend::query()->between($user->id, $friend->id)->first()?->status;
}

test('sending a request creates an outgoing and an incoming row', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    Sanctum::actingAs($alice);

    $this->postJson('/api/v1/friends', ['username' => strtoupper(" {$bob->username} ")])
        ->assertCreated()
        ->assertJson(['message' => 'Friend request sent.', 'accepted' => false])
        ->assertJsonPath('friend.id', $bob->id);

    expect(friendStatus($alice, $bob))->toBe(FriendStatus::OUTGOING_PENDING)
        ->and(friendStatus($bob, $alice))->toBe(FriendStatus::INCOMING_PENDING);
});

test('an unknown username is rejected', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/friends', ['username' => 'nobody_here'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username' => 'User not found']);

    expect(Friend::count())->toBe(0);
});

test('a user cannot add themselves', function () {
    $alice = User::factory()->create();
    Sanctum::actingAs($alice);

    $this->postJson('/api/v1/friends', ['username' => $alice->username])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('username');
});

test('a duplicate request is rejected', function () {
    [$alice, $bob] = User::factory()->count(2)->create();
    Sanctum::actingAs($alice);

    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    $this->postJson('/api/v1/friends', ['username' => $bob->username])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username' => 'Friend request already sent.']);
});

test('requesting someone who already requested you accepts it', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    Sanctum::actingAs($bob);
    $this->postJson('/api/v1/friends', ['username' => $alice->username])
        ->assertCreated()
        ->assertJson(['message' => "You're now friends.", 'accepted' => true])
        ->assertJsonPath('friend.id', $alice->id);

    expect(friendStatus($alice, $bob))->toBe(FriendStatus::FRIEND)
        ->and(friendStatus($bob, $alice))->toBe(FriendStatus::FRIEND);
});

test('accepting an incoming request makes both users friends', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    Sanctum::actingAs($bob);
    $this->postJson("/api/v1/friends/{$alice->id}/accept")->assertOk();

    $this->getJson('/api/v1/friends')
        ->assertOk()
        ->assertJsonPath('friends.0.id', $alice->id)
        ->assertJsonPath('friends.0.username', $alice->username)
        ->assertJsonCount(0, 'incoming');
});

test('the sender cannot accept their own outgoing request', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    $this->postJson("/api/v1/friends/{$bob->id}/accept")->assertNotFound();

    expect(friendStatus($alice, $bob))->toBe(FriendStatus::OUTGOING_PENDING);
});

test('the recipient sees the request as incoming', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    Sanctum::actingAs($bob);
    $this->getJson('/api/v1/friends')
        ->assertOk()
        ->assertJsonCount(0, 'friends')
        ->assertJsonPath('incoming.0.id', $alice->id);
});

test('declining a request removes both rows', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    Sanctum::actingAs($bob);
    $this->deleteJson("/api/v1/friends/{$alice->id}")->assertOk();

    expect(Friend::count())->toBe(0);
});

test('a request to a user who blocked you is rejected', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Friend::create(['user_id' => $bob->id, 'friend_id' => $alice->id, 'status' => FriendStatus::BLOCKED]);

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('username');

    $this->deleteJson("/api/v1/friends/{$bob->id}")->assertNotFound();

    expect(friendStatus($bob, $alice))->toBe(FriendStatus::BLOCKED)
        ->and(friendStatus($alice, $bob))->toBeNull();
});

test('unfriending removes both rows', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    Sanctum::actingAs($bob);
    $this->postJson("/api/v1/friends/{$alice->id}/accept")->assertOk();
    $this->deleteJson("/api/v1/friends/{$alice->id}")->assertOk();

    expect(Friend::count())->toBe(0);
});

test('blocking a friend leaves only the blocker row', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])->assertCreated();

    Sanctum::actingAs($bob);
    $this->postJson("/api/v1/friends/{$alice->id}/accept")->assertOk();

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/friends/{$bob->id}/block")->assertOk();

    expect(friendStatus($alice, $bob))->toBe(FriendStatus::BLOCKED)
        ->and(friendStatus($bob, $alice))->toBeNull();

    $this->getJson('/api/v1/friends')->assertJsonCount(0, 'friends');

    Sanctum::actingAs($bob);
    $this->getJson('/api/v1/friends')->assertJsonCount(0, 'friends');
    $this->postJson('/api/v1/friends', ['username' => $alice->username])->assertUnprocessable();
});

test('blocking does not erase the other side\'s block', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Friend::create(['user_id' => $bob->id, 'friend_id' => $alice->id, 'status' => FriendStatus::BLOCKED]);

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/friends/{$bob->id}/block")->assertOk();

    expect(friendStatus($alice, $bob))->toBe(FriendStatus::BLOCKED)
        ->and(friendStatus($bob, $alice))->toBe(FriendStatus::BLOCKED);
});

test('requesting a user you blocked lifts the block and sends the request', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/friends/{$bob->id}/block")->assertOk();

    $this->postJson('/api/v1/friends', ['username' => $bob->username])
        ->assertCreated()
        ->assertJson(['message' => 'Friend request sent.', 'accepted' => false]);

    expect(friendStatus($alice, $bob))->toBe(FriendStatus::OUTGOING_PENDING)
        ->and(friendStatus($bob, $alice))->toBe(FriendStatus::INCOMING_PENDING);
});

test('requesting a user you blocked is rejected when they blocked you back', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Friend::create(['user_id' => $alice->id, 'friend_id' => $bob->id, 'status' => FriendStatus::BLOCKED]);
    Friend::create(['user_id' => $bob->id, 'friend_id' => $alice->id, 'status' => FriendStatus::BLOCKED]);

    Sanctum::actingAs($alice);
    $this->postJson('/api/v1/friends', ['username' => $bob->username])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('username');

    expect(friendStatus($alice, $bob))->toBe(FriendStatus::BLOCKED)
        ->and(friendStatus($bob, $alice))->toBe(FriendStatus::BLOCKED);
});

test('unfriend cannot be used to lift your own block', function () {
    [$alice, $bob] = User::factory()->count(2)->create();

    Sanctum::actingAs($alice);
    $this->postJson("/api/v1/friends/{$bob->id}/block")->assertOk();
    $this->deleteJson("/api/v1/friends/{$bob->id}")->assertNotFound();

    expect(friendStatus($alice, $bob))->toBe(FriendStatus::BLOCKED);
});

test('a user cannot block themselves', function () {
    $alice = User::factory()->create();
    Sanctum::actingAs($alice);

    $this->postJson("/api/v1/friends/{$alice->id}/block")->assertUnprocessable();

    expect(Friend::count())->toBe(0);
});
