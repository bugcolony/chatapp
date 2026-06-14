<?php

use App\Http\Resources\Api\V1\MessageResource;
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
