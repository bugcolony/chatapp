<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_id' => $this->user_id,
            'channels' => ChannelResource::collection($this->whenLoaded('channels')),
            'members' => MemberResource::collection($this->whenLoaded('members')),
        ];
    }
}
