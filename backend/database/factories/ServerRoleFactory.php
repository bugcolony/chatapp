<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerRole>
 */
class ServerRoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'name' => fake()->unique()->jobTitle(),
            'color' => fake()->hexColor(),
            'permissions' => 0,
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state([
            'name' => ServerRole::BASE_ROLE_NAME,
            'is_system' => true,
            'color' => null,
        ]);
    }
}
