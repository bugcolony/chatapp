<?php

namespace App\Models;

use App\Http\Resources\Api\V1\MessageCollection;
use App\Http\Resources\Api\V1\MessageResource;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Attributes\UseResourceCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseResource(MessageResource::class)]
#[UseResourceCollection(MessageCollection::class)]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function attachment(): HasOne
    {
        return $this->hasOne(MessageAttachment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(MessageMention::class);
    }
}
