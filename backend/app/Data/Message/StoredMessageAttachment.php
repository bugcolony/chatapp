<?php

namespace App\Data\Message;

use Illuminate\Contracts\Support\Arrayable;

final readonly class StoredMessageAttachment implements Arrayable
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $originalName,
        public string $mimeType,
        public int $size,
    ) {}

    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
        ];
    }
}
