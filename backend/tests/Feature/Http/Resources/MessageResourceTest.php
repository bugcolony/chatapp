<?php

use App\Http\Resources\Api\V1\MessageResource;
use App\Models\File;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('message history does not include a client id', function () {
    $author = User::factory()->create();
    $message = Message::factory()->from($author)->create()->load('author');

    $data = (new MessageResource($message))->resolve();

    expect($data['client_id'])->toBeNull();
});

test('a newly created message may include its submitted client id', function () {
    $author = User::factory()->create();
    $message = Message::factory()->from($author)->create()->load('author');

    $data = (new MessageResource($message))
        ->withClientId(123)
        ->resolve();

    expect($data['client_id'])->toBe(123);
});

test('message attachments include persisted metadata and a protected url', function () {
    $author = User::factory()->create();
    $message = Message::factory()->from($author)->create();

    $file = File::create([
        'disk' => 'local',
        'source_path' => 'message-attachments/photo.jpg',
        'original_name' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 2048,
        'width' => 640,
        'height' => 480,
    ]);

    $message->attachment()->create(['file_id' => $file->id]);
    $message->load(['attachment.file', 'author']);

    $data = (new MessageResource($message))->response()->getData(true)['data'];

    expect($data['attachment'])->toBe([
        'name' => 'photo.jpg',
        'size' => 2048,
        'mime_type' => 'image/jpeg',
        'is_image' => true,
        'width' => 640,
        'height' => 480,
        'url' => "/api/v1/messages/{$message->id}/attachment",
    ]);
});
