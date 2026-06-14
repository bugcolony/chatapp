<?php

namespace App\Models;

use App\Enums\ChannelType;
use Database\Factories\ChannelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    /** @use HasFactory<ChannelFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'type' => ChannelType::class,
        'is_locked' => 'boolean',
        'position' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(ChannelPermissionOverride::class);
    }
}
