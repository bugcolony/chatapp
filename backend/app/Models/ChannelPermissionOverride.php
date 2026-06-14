<?php

namespace App\Models;

use Database\Factories\ChannelPermissionOverrideFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelPermissionOverride extends Model
{
    /** @use HasFactory<ChannelPermissionOverrideFactory> */
    use HasFactory;

    protected $guarded = [];

    public function serverRole(): BelongsTo
    {
        return $this->belongsTo(ServerRole::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
