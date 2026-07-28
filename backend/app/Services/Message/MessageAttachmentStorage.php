<?php

namespace App\Services\Message;

use App\Data\Message\StoredMessageAttachment;
use App\Exceptions\MessageAttachmentStorageException;
use App\Models\Channel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class MessageAttachmentStorage
{
    public function store(UploadedFile $file, Channel $channel): StoredMessageAttachment
    {
        $disk = (string) config('filesystems.default');

        try {
            $path = $file->store(
                "message-attachments/{$channel->server_id}/{$channel->id}",
                $disk,
            );
        } catch (Throwable $exception) {
            throw MessageAttachmentStorageException::writeFailed($disk, $exception);
        }

        if (! $path) {
            throw MessageAttachmentStorageException::writeFailed($disk);
        }

        return new StoredMessageAttachment(
            disk: $disk,
            path: $path,
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? 'application/octet-stream',
            size: $file->getSize(),
        );
    }

    public function delete(StoredMessageAttachment $attachment): void
    {
        try {
            Storage::disk($attachment->disk)->delete($attachment->path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
