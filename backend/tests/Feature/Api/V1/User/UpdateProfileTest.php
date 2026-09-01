<?php

use App\Models\File;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    config()->set('filesystems.default', 'local');
});

function fakeAvatarUpload(string $name = 'me.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        ),
    );
}

function avatarFileFor(User $user, string $path = 'avatars/existing.png'): File
{
    Storage::disk('local')->put($path, 'existing avatar');

    $file = File::create([
        'disk' => 'local',
        'source_path' => $path,
        'original_name' => 'existing.png',
        'mime_type' => 'image/png',
        'size' => 15,
    ]);

    $user->forceFill(['avatar_file_id' => $file->id])->save();

    return $file;
}

test('a name change is mirrored onto every membership nickname', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    $member = Member::factory()->create(['user_id' => $user->id, 'nickname' => 'Old Name']);
    $otherMember = Member::factory()->create(['nickname' => 'Someone Else']);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/me', ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('name', 'New Name');

    expect($member->refresh()->nickname)->toBe('New Name')
        ->and($otherMember->refresh()->nickname)->toBe('Someone Else');
});

test('an avatar upload is persisted when no other field is sent', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->post('/api/v1/me', ['avatar' => fakeAvatarUpload()], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('avatar', fn (?string $avatar): bool => $avatar !== null);

    $user->refresh();

    expect($user->avatar_file_id)->not->toBeNull()
        ->and(File::count())->toBe(1);

    Storage::disk('local')->assertExists($user->avatar->source_path);
});

test('replacing an avatar removes the previous file', function () {
    $user = User::factory()->create();
    $old = avatarFileFor($user);

    Sanctum::actingAs($user);

    $this->post('/api/v1/me', [
        'name' => 'Renamed',
        'avatar' => fakeAvatarUpload('new.png'),
    ], ['Accept' => 'application/json'])->assertOk();

    $user->refresh();

    expect($user->avatar_file_id)->not->toBe($old->id)
        ->and(File::find($old->id))->toBeNull()
        ->and(File::count())->toBe(1);
});

test('an avatar can be removed', function () {
    $user = User::factory()->create();
    $old = avatarFileFor($user);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/me', ['remove_avatar' => true])
        ->assertOk()
        ->assertJsonPath('avatar', null);

    expect($user->refresh()->avatar_file_id)->toBeNull()
        ->and(File::find($old->id))->toBeNull();
});

test('removing an avatar that was never set is a no-op', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/me', ['remove_avatar' => true])
        ->assertOk()
        ->assertJsonPath('avatar', null);

    expect($user->refresh()->avatar_file_id)->toBeNull();
});

test('an oversized avatar is rejected', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->post('/api/v1/me', [
        'avatar' => UploadedFile::fake()->create('huge.png', 2049, 'image/png'),
    ], ['Accept' => 'application/json'])->assertJsonValidationErrorFor('avatar');

    expect($user->refresh()->avatar_file_id)->toBeNull()
        ->and(File::count())->toBe(0);
});

test('a guest cannot update a profile', function () {
    $this->postJson('/api/v1/me', ['name' => 'Nobody'])->assertUnauthorized();
});
