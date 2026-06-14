<?php

namespace Database\Seeders;

use App\Enums\AppPermission;
use App\Enums\ChannelType;
use App\Enums\SystemRole;
use App\Models\Channel;
use App\Models\Member;
use App\Models\Message;
use App\Models\Server;
use App\Models\ServerRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FixturesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = User::factory()->count(15)->create();
        $users->each->assignRole(SystemRole::User->value);

        $serverSpecs = [
            ['name' => 'Gaming Lounge', 'channels' => ['general', 'lfg', 'clips', 'voice-chat', 'off-topic']],
            ['name' => 'Dev Cave', 'channels' => ['general', 'frontend', 'backend', 'devops', 'random']],
            ['name' => 'Music Crew', 'channels' => ['general', 'releases', 'production', 'gear', 'voice-jam']],
        ];

        foreach ($serverSpecs as $spec) {
            $owner = $users->random();

            $server = Server::factory()->create([
                'user_id' => $owner->id,
            ]);

            $baseRole = ServerRole::factory()->system()->create([
                'server_id' => $server->id,
                'name' => ServerRole::BASE_ROLE_NAME,
                'permissions' => AppPermission::basePermissions(),
            ]);

            $server->update(['base_role_id' => $baseRole->id]);

            $memberPool = $users->random(random_int(6, 10))->push($owner)->unique('id');

            $members = $memberPool->map(fn (User $u) => Member::factory()->create([
                'user_id' => $u->id,
                'server_id' => $server->id,
                'nickname' => fake()->userName(),
            ]));

            $channels = collect($spec['channels'])->map(function (string $name, int $i) use ($server) {
                $type = str_contains($name, 'voice') ? ChannelType::Voice : ChannelType::Text;

                return Channel::factory()->create([
                    'server_id' => $server->id,
                    'name' => $name,
                    'type' => $type,
                    'position' => $i,
                ]);
            });

            $textChannels = $channels->where('type', ChannelType::Text);

            foreach ($textChannels as $channel) {
                $count = random_int(20, 80);
                $timestamp = now()->subDays(14);
                for ($i = 0; $i < $count; $i++) {
                    $author = $members->random()->user_id;
                    $timestamp = $timestamp->addMinutes(random_int(5, 120));
                    Message::factory()->create([
                        'server_id' => $server->id,
                        'channel_id' => $channel->id,
                        'user_id' => $author,
                        'created_at' => $timestamp,
                    ]);
                }
            }
        }
    }
}
