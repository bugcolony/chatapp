<?php

namespace App\Models;

use App\Enums\ChannelType;
use App\Http\Resources\Api\V1\ChannelCollection;
use App\Http\Resources\Api\V1\ChannelResource;
use Database\Factories\ChannelFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Attributes\UseResourceCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseResource(ChannelResource::class)]
#[UseResourceCollection(ChannelCollection::class)]
class Channel extends Model
{
    /** @use HasFactory<ChannelFactory> */
    use HasFactory;
    use SoftDeletes;

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

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function voiceTextChannel(): HasOne
    {
        return $this->hasOne(self::class, 'parent_id')
            ->where('type', ChannelType::VoiceText);
    }

    public function messageChannelId(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->type) {
                ChannelType::Text, ChannelType::VoiceText => $this->id,
                ChannelType::Voice => $this->voiceTextChannel?->id,
                default => null
            }
        );
    }
}
