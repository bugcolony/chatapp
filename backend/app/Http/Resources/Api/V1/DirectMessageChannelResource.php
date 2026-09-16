<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DirectMessageChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'last_message_id' => $this->last_message_id,
            'last_read_id' => max((int) $this->last_read_id, (int) $this->pivot?->hidden_before_message_id),
            'participants' => FriendResource::collection($this->participants),
        ];
    }
}
