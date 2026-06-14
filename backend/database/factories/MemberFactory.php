<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'server_id' => Server::factory(),
            'nickname' => fake()->boolean(30) ? fake()->userName() : null,
        ];
    }
}
