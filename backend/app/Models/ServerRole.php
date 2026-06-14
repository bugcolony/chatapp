<?php

namespace App\Models;

use Database\Factories\ServerRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerRole extends Model
{
    /** @use HasFactory<ServerRoleFactory> */
    use HasFactory;

    public const string BASE_ROLE_NAME = 'everyone';

    protected $guarded = [];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(ChannelPermissionOverride::class);
    }
}
