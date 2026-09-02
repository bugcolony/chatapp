<?php

namespace App\Models;

use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleted(static function (Server $server): void {
            if ($server->isForceDeleting()) {
                return;
            }

            $server->channels()->delete();
        });

        static::restoring(static function (Server $server): void {
            $server->channels()
                ->onlyTrashed()
                ->where('deleted_at', '>=', $server->deleted_at)
                ->restore();
        });
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ServerRole::class);
    }

    public function baseRole(): BelongsTo
    {
        return $this->belongsTo(ServerRole::class, 'base_role_id');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(ServerInvite::class);
    }
}
