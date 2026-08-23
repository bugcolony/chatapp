<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\ChannelType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'parent_id' => $this->parent_id,
            'type' => $this->type,
            'message_channel_id' => $this->message_channel_id,
            'name' => $this->name,
        ];
    }
}
