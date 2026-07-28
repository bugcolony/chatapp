<?php

use App\Events\MessageCreated;
use App\Models\Channel;
use App\Models\Member;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function messageChannelFixture(): array
{
    $user = User::factory()->create();
    $server = Server::factory()->for($user, 'owner')->create();
    $channel = Channel::factory()->for($server)->create();

    Member::factory()->for($user)->for($server)->create();
    Sanctum::actingAs($user);

    return compact('user', 'server', 'channel');
}

function seedMessageAttachmentUsage(
    User $user,
    Channel $channel,
    int $size,
    bool $softDeleted = false,
): Message {
    $message = Message::factory()
        ->inChannel($channel)
        ->from($user)
        ->create();

    $message->attachment()->create([
        'disk' => 'local',
        'path' => 'existing/'.fake()->uuid(),
        'original_name' => 'existing.bin',
        'mime_type' => 'application/octet-stream',
        'size' => $size,
    ]);

    if ($softDeleted) {
        $message->delete();
    }

    return $message;
}

beforeEach(function () {
    Event::fake([MessageCreated::class]);
    Storage::fake('local');
    config()->set('filesystems.default', 'local');
});

test('a message can contain one image attachment without text', function () {
    ['channel' => $channel] = messageChannelFixture();
    $file = UploadedFile::fake()->createWithContent(
        'weekend.png',
        base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        ),
    );

    $response = $this->post("/api/v1/channels/{$channel->id}/messages", [
        'client_id' => 123,
        'attachment' => $file,
    ])->assertCreated();

    $message = Message::query()->sole();
    $attachment = MessageAttachment::query()->sole();

    expect($message->content)->toBeNull()
        ->and($attachment->message_id)->toBe($message->id)
        ->and($attachment->disk)->toBe('local')
        ->and($attachment->original_name)->toBe('weekend.png')
        ->and($attachment->mime_type)->toBe('image/png')
        ->and($attachment->size)->toBeGreaterThan(0);

    Storage::disk('local')->assertExists($attachment->path);

    $response
        ->assertJsonPath('data.client_id', 123)
        ->assertJsonPath('data.attachment.name', 'weekend.png')
        ->assertJsonPath('data.attachment.is_image', true)
        ->assertJsonPath(
            'data.attachment.url',
            "/api/v1/messages/{$message->id}/attachment",
        );

    $this->get($response->json('data.attachment.url'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('a non-image attachment is delivered as a download', function () {
    ['channel' => $channel] = messageChannelFixture();
    $file = UploadedFile::fake()->create('notes.txt', 4, 'text/plain');

    $response = $this->post("/api/v1/channels/{$channel->id}/messages", [
        'content' => 'Meeting notes',
        'client_id' => 456,
        'attachment' => $file,
    ])->assertCreated();

    $this->get($response->json('data.attachment.url'))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename=notes.txt');
});

test('an unauthenticated attachment request returns JSON instead of redirecting', function () {
    $this->get('/api/v1/messages/1/attachment', [
        'Accept' => 'text/html',
    ])
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('a message requires text or an attachment', function () {
    ['channel' => $channel] = messageChannelFixture();

    $this->postJson("/api/v1/channels/{$channel->id}/messages", [
        'client_id' => 789,
    ])->assertJsonValidationErrors(['content', 'attachment']);
});

test('an attachment of exactly two million bytes is accepted', function () {
    ['channel' => $channel] = messageChannelFixture();
    $file = UploadedFile::fake()->createWithContent(
        'archive.zip',
        str_repeat('a', 2_000_000),
    );

    $this->withHeader('Accept', 'application/json')
        ->post("/api/v1/channels/{$channel->id}/messages", [
            'client_id' => 998,
            'attachment' => $file,
        ])
        ->assertCreated();
});

test('oversized attachments are rejected', function () {
    ['channel' => $channel] = messageChannelFixture();
    $file = UploadedFile::fake()->createWithContent(
        'archive.zip',
        str_repeat('a', 2_100_000),
    );

    $this->withHeader('Accept', 'application/json')
        ->post("/api/v1/channels/{$channel->id}/messages", [
            'client_id' => 999,
            'attachment' => $file,
        ])
        ->assertJsonValidationErrors('attachment');

    expect(Message::query()->count())->toBe(0);
    expect(MessageAttachment::query()->count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('/');
});

test('a user can fill the attachment quota to exactly ten million bytes', function () {
    ['user' => $user, 'channel' => $channel] = messageChannelFixture();
    seedMessageAttachmentUsage($user, $channel, 9_999_999);

    $this->withHeader('Accept', 'application/json')
        ->post("/api/v1/channels/{$channel->id}/messages", [
            'client_id' => 1001,
            'attachment' => UploadedFile::fake()->createWithContent('last-byte.txt', 'a'),
        ])
        ->assertCreated();

    $usedBytes = (int) MessageAttachment::query()
        ->join('messages', 'messages.id', '=', 'message_attachments.message_id')
        ->where('messages.user_id', $user->id)
        ->sum('message_attachments.size');

    expect($usedBytes)->toBe(10_000_000);
});

test('an upload that would exceed the user attachment quota is rejected before storage', function () {
    ['user' => $user, 'channel' => $channel] = messageChannelFixture();
    seedMessageAttachmentUsage($user, $channel, 9_999_999);

    $this->withHeader('Accept', 'application/json')
        ->post("/api/v1/channels/{$channel->id}/messages", [
            'client_id' => 1002,
            'attachment' => UploadedFile::fake()->createWithContent('two-bytes.txt', 'ab'),
        ])
        ->assertForbidden();

    expect(Message::query()->count())->toBe(1)
        ->and(MessageAttachment::query()->count())->toBe(1);
    Storage::disk('local')->assertDirectoryEmpty('/');
});

test('attachments on soft-deleted messages still count toward the user quota', function () {
    ['user' => $user, 'channel' => $channel] = messageChannelFixture();
    seedMessageAttachmentUsage($user, $channel, 10_000_000, softDeleted: true);

    $this->withHeader('Accept', 'application/json')
        ->post("/api/v1/channels/{$channel->id}/messages", [
            'client_id' => 1003,
            'attachment' => UploadedFile::fake()->createWithContent('blocked.txt', 'a'),
        ])
        ->assertForbidden();

    expect(Message::withTrashed()->count())->toBe(1)
        ->and(MessageAttachment::query()->count())->toBe(1);
    Storage::disk('local')->assertDirectoryEmpty('/');
});

test('attachment usage is isolated per user', function () {
    ['user' => $user, 'channel' => $channel] = messageChannelFixture();
    $otherUser = User::factory()->create();
    seedMessageAttachmentUsage($otherUser, $channel, 10_000_000);

    $this->withHeader('Accept', 'application/json')
        ->post("/api/v1/channels/{$channel->id}/messages", [
            'client_id' => 1004,
            'attachment' => UploadedFile::fake()->createWithContent('mine.txt', 'a'),
        ])
        ->assertCreated();

    expect(MessageAttachment::query()->count())->toBe(2);
});

test('an unavailable attachment disk returns a controlled service response', function () {
    ['channel' => $channel] = messageChannelFixture();

    Log::spy();
    config()->set('filesystems.default', 'broken');
    config()->set('filesystems.disks.broken', [
        'driver' => 'local',
        'root' => '/dev/null',
        'throw' => false,
    ]);

    $file = UploadedFile::fake()->create('notes.txt', 4, 'text/plain');

    $this->withHeader('Accept', 'application/json')
        ->post("/api/v1/channels/{$channel->id}/messages", [
            'client_id' => 1000,
            'attachment' => $file,
        ])
        ->assertServiceUnavailable()
        ->assertExactJson([
            'message' => 'Attachment storage is temporarily unavailable.',
        ]);

    expect(Message::query()->count())->toBe(0)
        ->and(MessageAttachment::query()->count())->toBe(0);
});
