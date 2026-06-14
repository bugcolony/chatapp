<?php

namespace Database\Factories;

use App\Enums\AuthProvider;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => $this->faker->randomElement(AuthProvider::cases()),
            'provider_id' => Str::random(10),
            'token' => Str::random(40),
            'refresh_token' => Str::random(40),
        ];
    }
}
