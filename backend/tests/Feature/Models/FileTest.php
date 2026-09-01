<?php

use App\Jobs\DeleteFileObjects;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fileRecord(array $attributes = []): File
{
    return File::create(array_merge([
        'disk' => 'local',
        'source_path' => 'uploads/original.png',
        'original_name' => 'original.png',
        'mime_type' => 'image/png',
        'size' => 128,
    ], $attributes));
}

test('deleting a file queues removal of both of its objects', function () {
    $file = fileRecord(['path' => 'uploads/derived.webp']);

    Queue::fake();

    $file->delete();

    Queue::assertPushed(DeleteFileObjects::class, function (DeleteFileObjects $job): bool {
        return $job->disk === 'local'
            && $job->files === ['uploads/original.png', 'uploads/derived.webp'];
    });
});

test('a file without a derived object queues only the source path', function () {
    $file = fileRecord();

    Queue::fake();

    $file->delete();

    Queue::assertPushed(DeleteFileObjects::class, function (DeleteFileObjects $job): bool {
        return $job->files === ['uploads/original.png'];
    });
});

test('the queued job removes the objects from the disk', function () {
    Storage::fake('local');
    Storage::disk('local')->put('uploads/original.png', 'bytes');
    Storage::disk('local')->put('uploads/derived.webp', 'bytes');

    (new DeleteFileObjects('local', ['uploads/original.png', 'uploads/derived.webp']))->handle();

    Storage::disk('local')->assertMissing('uploads/original.png');
    Storage::disk('local')->assertMissing('uploads/derived.webp');
});
