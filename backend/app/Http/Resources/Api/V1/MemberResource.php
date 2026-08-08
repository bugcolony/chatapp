<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'display_name' => $this->nickname ?? $this->user->name,
            'status' => 'offline',
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
