<?php

namespace App\Models;

use App\Enums\FriendStatus;
use App\Services\DemoFixtureManager;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'username',
        'onboarded_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function ownedServers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function servers(): HasManyThrough
    {
        return $this->hasManyThrough(
            Server::class,
            Member::class,
            'user_id',
            'id',
            'id',
            'server_id'
        );
    }

    public function serverRoles(): BelongsToMany
    {
        return $this->belongsToMany(ServerRole::class);
    }

    public function activeServers(): HasManyThrough
    {
        return $this->servers()->whereNull('members.left_at');
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(ChannelPermissionOverride::class);
    }

    public function messageMentions(): HasMany
    {
        return $this->hasMany(MessageMention::class);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(File::class, 'avatar_file_id');
    }

    public function channelReads(): HasMany
    {
        return $this->hasMany(ChannelRead::class);
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'user_id',
            'friend_id'
        )->wherePivot('status', FriendStatus::FRIEND->value);
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'user_id',
            'friend_id'
        )->wherePivot('status', FriendStatus::BLOCKED->value);
    }

    public function incomingFriendRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'user_id',
            'friend_id'
        )->wherePivot('status', FriendStatus::INCOMING_PENDING->value);
    }

    public function sentFriendRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'user_id',
            'friend_id'
        )->wherePivot('status', FriendStatus::OUTGOING_PENDING->value);
    }

    public function directMessageChannels(): BelongsToMany
    {
        return $this->belongsToMany(
            Channel::class,
            'channel_participants',
            'user_id',
            'channel_id'
        )->where(fn ($query) => $query
            ->whereNull('channel_participants.hidden_before_message_id')
            ->orWhereColumn('channel_participants.hidden_before_message_id', '<', 'channels.last_message_id'));
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_file_id
            ? route('users.avatar', ['user' => $this->id, 'v' => $this->avatar_file_id], absolute: false)
            : null;
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null && $this->username !== null;
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function isDemo(): bool
    {
        return DemoFixtureManager::isDemoEmail($this->email);
    }

    public function deleteAvatar(): void
    {
        $avatar = $this->avatar;

        if ($avatar === null) {
            return;
        }

        $this->avatar()->dissociate();
        $this->save();

        $avatar->delete();
    }
}
