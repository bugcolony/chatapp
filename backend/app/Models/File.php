<?php

namespace App\Models;

use App\Enums\FileStatus;
use App\Jobs\DeleteFileObjects;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    private const array IMAGE_TYPES = [
        'image/avif',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
            'status' => FileStatus::class,
        ];
    }

    public static function booted(): void
    {
        static::deleted(static function (File $file) {
            DeleteFileObjects::dispatch($file->disk, array_filter([
                $file->source_path, $file->path
            ]))->afterCommit();
        });
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, self::IMAGE_TYPES, true);
    }

    public function servedPath(): string
    {
        return $this->path ?? $this->source_path;
    }
}
