<?php

namespace App\Data\Server;

use Illuminate\Contracts\Support\Arrayable;

final readonly class CreateServerData implements Arrayable
{
    public function __construct(public string $name) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
