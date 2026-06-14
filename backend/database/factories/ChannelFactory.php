<?php

namespace Database\Factories;

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'name' => fake()->unique()->slug(2),
            'type' => ChannelType::Text,
            'position' => 0,
            'is_locked' => false,
        ];
    }

    public function voice(): static
    {
        return $this->state(['type' => ChannelType::Voice]);
    }

    public function category(): static
    {
        return $this->state(['type' => ChannelType::Category]);
    }

    public function locked(): static
    {
        return $this->state(['is_locked' => true]);
    }
}
