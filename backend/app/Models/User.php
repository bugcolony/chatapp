<?php

namespace App\Models;

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
}
