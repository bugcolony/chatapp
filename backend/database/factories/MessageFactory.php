<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'server_id' => fn (array $attrs) => Channel::find($attrs['channel_id'])->server_id,
            'user_id' => User::factory(),
            'content' => fake()->realText(random_int(20, 280)),
        ];
    }

    public function inChannel(Channel $channel): static
    {
        return $this->state([
            'server_id' => $channel->server_id,
            'channel_id' => $channel->id,
        ]);
    }

    public function from(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
