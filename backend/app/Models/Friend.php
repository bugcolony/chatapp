<?php

namespace App\Models;

use App\Enums\FriendStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    protected $guarded = [];
    protected $casts = [
        'status' => FriendStatus::class,
    ];

    #[Scope]
    protected function between(Builder $query, int $userId, int $friendId): void
    {
        $query->where('user_id', $userId)->where('friend_id', $friendId);
    }
}
