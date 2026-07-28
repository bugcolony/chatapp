<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    private const array PREVIEWABLE_IMAGE_TYPES = [
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
        ];
    }

    public function isPreviewableImage(): bool
    {
        return in_array($this->mime_type, self::PREVIEWABLE_IMAGE_TYPES, true);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
