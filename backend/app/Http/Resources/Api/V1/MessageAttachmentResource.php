<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->file->original_name,
            'size' => $this->file->size,
            'mime_type' => $this->file->mime_type,
            'is_image' => $this->file->isImage(),
            'width' => $this->file->width,
            'height' => $this->file->height,
            'url' => route('messages.attachment', $this->message_id, absolute: false),
        ];
    }
}
