<?php

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    config()->set('filesystems.default', 'local');
});

function userWithAvatar(string $path = 'avatars/face.png', bool $writeObject = true): array
{
    if ($writeObject) {
        Storage::disk('local')->put($path, 'avatar bytes');
    }

    $file = File::create([
        'disk' => 'local',
        'source_path' => $path,
        'original_name' => 'face.png',
        'mime_type' => 'image/png',
        'size' => 12,
    ]);

    $user = User::factory()->create(['avatar_file_id' => $file->id]);

    return [$user, $file];
}

test('an avatar is served to an authenticated user', function () {
    [$owner, $file] = userWithAvatar();

    Sanctum::actingAs(User::factory()->create());

    $response = $this->get("/api/v1/users/{$owner->id}/avatar?v={$file->id}");

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');

    expect($response->streamedContent())->toBe('avatar bytes');
});

test('an avatar requested without the current version is not cached', function () {
    [$owner, $file] = userWithAvatar();

    Sanctum::actingAs(User::factory()->create());

    $this->get("/api/v1/users/{$owner->id}/avatar")
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-cache, private');

    $this->get("/api/v1/users/{$owner->id}/avatar?v=".($file->id + 1))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-cache, private');
});

test('a user without an avatar returns not found', function () {
    $owner = User::factory()->create();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/users/{$owner->id}/avatar")->assertNotFound();
});

test('an avatar whose object is missing from the disk returns not found', function () {
    [$owner] = userWithAvatar(writeObject: false);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/users/{$owner->id}/avatar")->assertNotFound();
});

test('a guest cannot read an avatar', function () {
    [$owner] = userWithAvatar();

    $this->getJson("/api/v1/users/{$owner->id}/avatar")->assertUnauthorized();
});

test('a user who has not onboarded cannot read an avatar', function () {
    [$owner] = userWithAvatar();

    Sanctum::actingAs(User::factory()->notOnboarded()->create());

    $this->getJson("/api/v1/users/{$owner->id}/avatar")->assertForbidden();
});
