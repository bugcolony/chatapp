<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    protected ?int $clientId = null;

    public function withClientId(int $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mine' => $this->user_id === auth()->id(),
            'author' => $this->author->name,
            'mentions' => MessageMentionResource::collection($this->whenLoaded('mentions')),
            'created_at' => $this->created_at,
            'message' => $this->content,
            'attachment' => MessageAttachmentResource::make($this->whenLoaded('attachment')),
            'client_id' => $this->clientId,
        ];
    }
}
