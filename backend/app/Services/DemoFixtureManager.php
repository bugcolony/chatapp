<?php

namespace App\Services;

use App\Enums\AppPermission;
use App\Enums\SystemRole;
use App\Models\Channel;
use App\Models\Member;
use App\Models\Message;
use App\Models\Server;
use App\Models\ServerRole;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoFixtureManager
{
    private const string LOCK_NAME = 'demo-fixtures-lifecycle';

    public const string PASSWORD = 'password';

    public const string OWNER_EMAIL = 'example1@example.com';

    public const array USERS = [
        ['name' => 'Example User 1', 'email' => self::OWNER_EMAIL],
        ['name' => 'Example User 2', 'email' => 'example2@example.com'],
        ['name' => 'Example User 3', 'email' => 'example3@example.com'],
        ['name' => 'Example User 4', 'email' => 'example4@example.com'],
        ['name' => 'Example User 5', 'email' => 'example5@example.com'],
        ['name' => 'Example User 6', 'email' => 'example6@example.com'],
        ['name' => 'Example User 7', 'email' => 'example7@example.com'],
        ['name' => 'Example User 8', 'email' => 'example8@example.com'],
        ['name' => 'Example User 9', 'email' => 'example9@example.com'],
        ['name' => 'Example User 10', 'email' => 'example10@example.com'],
    ];

    public const array SERVERS = [
        [
            'name' => 'Gaming Lounge',
            'channels' => [
                ['name' => 'Lobby', 'type' => 'category'],
                ['name' => 'general', 'type' => 'text', 'parent' => 'Lobby'],
                ['name' => 'off-topic', 'type' => 'text', 'parent' => 'Lobby'],
                ['name' => 'Games', 'type' => 'category'],
                ['name' => 'lfg', 'type' => 'text', 'parent' => 'Games'],
                ['name' => 'clips', 'type' => 'text', 'parent' => 'Games'],
                ['name' => 'voice-chat', 'type' => 'voice', 'parent' => 'Games'],
            ],
            'messages' => [
                'Welcome to the Gaming Lounge!',
                'What is everyone playing this week?',
                'I am looking for a co-op group tonight.',
                'Drop your best clips in the clips channel.',
                'Has anyone tried the latest update yet?',
                'I can join a match after dinner.',
                'The new map is quickly becoming my favorite.',
                'I will be in voice-chat if anyone wants to join.',
                'A weekend tournament could be fun.',
                'Good luck and have fun, everyone!',
            ],
        ],
        [
            'name' => 'Dev Cave',
            'channels' => [
                ['name' => 'Community', 'type' => 'category'],
                ['name' => 'general', 'type' => 'text', 'parent' => 'Community'],
                ['name' => 'random', 'type' => 'text', 'parent' => 'Community'],
                ['name' => 'Development', 'type' => 'category'],
                ['name' => 'frontend', 'type' => 'text', 'parent' => 'Development'],
                ['name' => 'backend', 'type' => 'text', 'parent' => 'Development'],
                ['name' => 'devops', 'type' => 'text', 'parent' => 'Development'],
            ],
            'messages' => [
                'Welcome to the Dev Cave!',
                'What is everyone building at the moment?',
                'I am cleaning up a stubborn integration test today.',
                'The frontend channel has a new component proposal.',
                'I left some API notes in the backend channel.',
                'The deployment pipeline is looking much faster now.',
                'Does anyone want to pair on a review later?',
                'I found a useful pattern for the permissions code.',
                'Remember to add a test for the edge case.',
                'Shipping small changes is still my favorite workflow.',
            ],
        ],
        [
            'name' => 'Music Crew',
            'channels' => [
                ['name' => 'Community', 'type' => 'category'],
                ['name' => 'general', 'type' => 'text', 'parent' => 'Community'],
                ['name' => 'releases', 'type' => 'text', 'parent' => 'Community'],
                ['name' => 'Studio', 'type' => 'category'],
                ['name' => 'production', 'type' => 'text', 'parent' => 'Studio'],
                ['name' => 'gear', 'type' => 'text', 'parent' => 'Studio'],
                ['name' => 'voice-jam', 'type' => 'voice', 'parent' => 'Studio'],
            ],
            'messages' => [
                'Welcome to the Music Crew!',
                'What albums have been on repeat lately?',
                'I posted a new mix in the releases channel.',
                'The bass line finally sits nicely in the track.',
                'I am comparing a couple of small audio interfaces.',
                'A listening party this Friday would be great.',
                'The production notes from yesterday were really helpful.',
                'I can join a voice jam later this evening.',
                'Share anything new you discover this week.',
                'There is always room for one more playlist.',
            ],
        ],
    ];

    public function provision(): void
    {
        $this->withLifecycleLock(function (): void {
            Model::withoutEvents(function (): void {
                DB::transaction(fn () => $this->provisionFixtures());
            });

            $this->forgetPermissionCache();
        });
    }

    public function reset(): void
    {
        $this->withLifecycleLock(function (): void {
            Model::withoutEvents(function (): void {
                DB::transaction(function (): void {
                    $this->removeFixtures();
                    $this->provisionFixtures();
                });
            });

            $this->forgetPermissionCache();
        });
    }

    public function remove(): void
    {
        $this->withLifecycleLock(function (): void {
            DB::transaction(fn () => $this->removeFixtures());

            $this->forgetPermissionCache();
        });
    }

    private function provisionFixtures(): void
    {
        $systemRoles = [];

        foreach (SystemRole::cases() as $role) {
            $systemRoles[$role->value] = Role::findOrCreate($role->value);
        }

        $systemRole = $systemRoles[SystemRole::User->value];
        $users = [];

        foreach (self::USERS as $spec) {
            $user = User::query()->firstOrNew(['email' => $spec['email']]);
            $attributes = [
                'name' => $spec['name'],
                'email' => $spec['email'],
                'email_verified_at' => $user->email_verified_at ?? now(),
                'deleted_at' => null,
            ];

            if (! $user->exists || ! Hash::check(self::PASSWORD, $user->password)) {
                $attributes['password'] = self::PASSWORD;
            }

            $user->forceFill($attributes);

            if (! $user->exists || $user->isDirty()) {
                $user->save();
            }

            $user->syncRoles([$systemRole]);
            $users[$user->email] = $user;
        }

        $owner = $users[self::OWNER_EMAIL];
        $orderedUsers = array_values($users);

        foreach (self::SERVERS as $serverPosition => $serverSpec) {
            $server = Server::query()->firstOrNew([
                'user_id' => $owner->id,
                'name' => $serverSpec['name'],
            ]);

            $server->forceFill([
                'name' => $serverSpec['name'],
                'user_id' => $owner->id,
                'deleted_at' => null,
            ]);

            if (! $server->exists || $server->isDirty()) {
                $server->save();
            }

            $baseRole = ServerRole::query()->updateOrCreate([
                'server_id' => $server->id,
                'name' => ServerRole::BASE_ROLE_NAME,
            ], [
                'color' => null,
                'permissions' => AppPermission::basePermissions(),
                'is_system' => true,
            ]);

            if ($server->base_role_id !== $baseRole->id) {
                $server->baseRole()->associate($baseRole)->save();
            }

            foreach ($orderedUsers as $user) {
                Member::query()->updateOrCreate([
                    'user_id' => $user->id,
                    'server_id' => $server->id,
                ], [
                    'nickname' => null,
                    'left_at' => null,
                    'pin_position' => $serverPosition,
                ]);
            }

            $channels = [];

            foreach ($serverSpec['channels'] as $channelPosition => $channelSpec) {
                $channel = Channel::withTrashed()->firstOrNew([
                    'server_id' => $server->id,
                    'name' => $channelSpec['name'],
                ]);

                $channel->forceFill([
                    'type' => $channelSpec['type'],
                    'position' => $channelPosition,
                    'is_locked' => false,
                    'deleted_at' => null,
                ]);

                if ($channelSpec['type'] === 'category') {
                    $channel->parent_id = null;
                }

                if (! $channel->exists || $channel->isDirty()) {
                    $channel->save();
                }

                $channels[$channel->name] = $channel;
            }

            foreach ($serverSpec['channels'] as $channelSpec) {
                $channel = $channels[$channelSpec['name']];
                $parentId = isset($channelSpec['parent'])
                    ? $channels[$channelSpec['parent']]->id
                    : null;

                if ($channel->parent_id !== $parentId) {
                    $channel->update(['parent_id' => $parentId]);
                }
            }

            $general = $channels['general'];
            $messageCount = count($serverSpec['messages']);

            foreach ($serverSpec['messages'] as $messagePosition => $content) {
                $author = $orderedUsers[$messagePosition % count($orderedUsers)];
                $message = Message::withTrashed()->firstOrNew([
                    'server_id' => $server->id,
                    'channel_id' => $general->id,
                    'user_id' => $author->id,
                    'content' => $content,
                ]);

                if (! $message->exists) {
                    $timestamp = now()->subMinutes(($messageCount - $messagePosition) * 4);
                    $message->created_at = $timestamp;
                    $message->updated_at = $timestamp;
                    $message->save();
                } elseif ($message->trashed()) {
                    $message->restore();
                }
            }
        }
    }

    private function removeFixtures(): void
    {
        $emails = array_column(self::USERS, 'email');
        $userIds = DB::table('users')
            ->whereIn('email', $emails)
            ->lockForUpdate()
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();

        DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();

        if ($userIds === []) {
            return;
        }

        $serverIds = DB::table('servers')
            ->whereIn('user_id', $userIds)
            ->lockForUpdate()
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();
        $channelIds = $serverIds === []
            ? []
            : DB::table('channels')
                ->whereIn('server_id', $serverIds)
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->all();
        $serverRoleIds = $serverIds === []
            ? []
            : DB::table('server_roles')
                ->whereIn('server_id', $serverIds)
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->all();

        if ($serverIds !== [] || $serverRoleIds !== []) {
            DB::table('servers')
                ->where(function (Builder $query) use ($serverIds, $serverRoleIds): void {
                    if ($serverIds !== []) {
                        $query->whereIn('id', $serverIds);
                    }

                    if ($serverRoleIds !== []) {
                        $method = $serverIds === [] ? 'whereIn' : 'orWhereIn';
                        $query->{$method}('base_role_id', $serverRoleIds);
                    }
                })
                ->update(['base_role_id' => null]);
        }

        $this->deleteMatching('server_invites', [
            'server_id' => $serverIds,
            'channel_id' => $channelIds,
            'created_by' => $userIds,
        ]);
        $this->deleteMatching('channel_permission_overrides', [
            'channel_id' => $channelIds,
            'server_role_id' => $serverRoleIds,
            'user_id' => $userIds,
        ]);
        $this->deleteMatching('messages', [
            'server_id' => $serverIds,
            'channel_id' => $channelIds,
            'user_id' => $userIds,
        ]);
        $this->deleteMatching('server_role_user', [
            'server_id' => $serverIds,
            'server_role_id' => $serverRoleIds,
            'user_id' => $userIds,
        ]);
        $this->deleteMatching('members', [
            'server_id' => $serverIds,
            'user_id' => $userIds,
        ]);

        if ($channelIds !== []) {
            DB::table('channels')->whereIn('parent_id', $channelIds)->update(['parent_id' => null]);
            DB::table('channels')->whereIn('id', $channelIds)->delete();
        }

        if ($serverRoleIds !== []) {
            DB::table('server_roles')->whereIn('id', $serverRoleIds)->delete();
        }

        if ($serverIds !== []) {
            DB::table('servers')->whereIn('id', $serverIds)->delete();
        }

        $morphType = (new User)->getMorphClass();

        DB::table('personal_access_tokens')
            ->where('tokenable_type', $morphType)
            ->whereIn('tokenable_id', $userIds)
            ->delete();
        DB::table('social_accounts')->whereIn('user_id', $userIds)->delete();

        $permissionTables = config('permission.table_names');
        $modelMorphKey = config('permission.column_names.model_morph_key');

        foreach (['model_has_roles', 'model_has_permissions'] as $tableKey) {
            DB::table($permissionTables[$tableKey])
                ->where('model_type', $morphType)
                ->whereIn($modelMorphKey, $userIds)
                ->delete();
        }

        DB::table('users')->whereIn('id', $userIds)->delete();
    }

    /**
     * @param  array<string, array<int>>  $columns
     */
    private function deleteMatching(string $table, array $columns): void
    {
        $columns = array_filter($columns, static fn (array $ids): bool => $ids !== []);

        if ($columns === []) {
            return;
        }

        DB::table($table)
            ->where(function (Builder $query) use ($columns): void {
                $first = true;

                foreach ($columns as $column => $ids) {
                    $method = $first ? 'whereIn' : 'orWhereIn';
                    $query->{$method}($column, $ids);
                    $first = false;
                }
            })
            ->delete();
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function withLifecycleLock(Closure $callback): void
    {
        Cache::lock(self::LOCK_NAME, 60)->block(10, $callback);
    }
}
