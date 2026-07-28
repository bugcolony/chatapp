<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}
