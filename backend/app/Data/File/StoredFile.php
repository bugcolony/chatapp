<?php

namespace App\Data\File;

use App\Enums\FileStatus;
use Illuminate\Contracts\Support\Arrayable;

final readonly class StoredFile implements Arrayable
{
    public function __construct(
        public string $disk,
        public string $sourcePath,
        public string $originalName,
        public string $mimeType,
        public int $size,
        public ?int $width = null,
        public ?int $height = null,
        public FileStatus $status = FileStatus::READY,
    ) {}

    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'source_path' => $this->sourcePath,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'status' => $this->status,
        ];
    }
}
