<?php

namespace App\Services\File;

use App\Data\File\StoredFile;
use App\Enums\FileStatus;
use App\Exceptions\FileStorageException;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class FileStorage
{
    private const array IMAGE_TYPES = [
        'image/avif',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function storeToDisk(UploadedFile $upload, string $directory): StoredFile
    {
        $disk = (string) config('filesystems.default');
        $mimeType = $upload->getMimeType() ?? 'application/octet-stream';
        $isImage = in_array($mimeType, self::IMAGE_TYPES, true);

        [$width, $height] = $this->dimensions($upload, $isImage);

        try {
            $path = $upload->store($directory, $disk);
        } catch (Throwable $exception) {
            throw FileStorageException::writeFailed($disk, $exception);
        }

        if (! $path) {
            throw FileStorageException::writeFailed($disk);
        }

        return new StoredFile(
            disk: $disk,
            sourcePath: $path,
            originalName: $upload->getClientOriginalName(),
            mimeType: $mimeType,
            size: $upload->getSize(),
            width: $width,
            height: $height,
            status: $isImage ? FileStatus::PENDING : FileStatus::READY,
        );
    }

    public function persistToDb(StoredFile $stored): File
    {
        return File::create($stored->toArray());
    }

    public function delete(StoredFile $stored): void
    {
        try {
            Storage::disk($stored->disk)->delete($stored->sourcePath);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function dimensions(UploadedFile $upload, bool $isImage): array
    {
        if (! $isImage) {
            return [null, null];
        }

        $size = @getimagesize($upload->getRealPath());

        return $size ? [$size[0], $size[1]] : [null, null];
    }
}
