<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->original_name,
            'size' => $this->size,
            'mime_type' => $this->mime_type,
            'is_image' => $this->isPreviewableImage(),
            'url' => route('messages.attachment', $this->message_id, absolute: false),
        ];
    }
}
