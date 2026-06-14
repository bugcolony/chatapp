<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerInviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'expires_at' => $this->expires_at,
            'max_uses' => $this->max_uses,
            'server' => ServerResource::make($this->whenLoaded('server')),
        ];
    }
}
