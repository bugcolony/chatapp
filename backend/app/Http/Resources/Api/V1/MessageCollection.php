<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MessageCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'has_more_pages' => $this->resource->hasMorePages(),
                'cursor' => $paginated['next_cursor'] ?? null,
                'per_page' => $paginated['per_page'],
            ],
        ];
    }
}
