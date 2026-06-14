<?php

namespace App\Actions\Invite;

use App\Models\Member;
use App\Models\ServerInvite;
use App\Models\User;

class JoinServerWithInvite
{
    public function execute(User $actor, ServerInvite $invite): Member
    {
        $membership = Member::query()->updateOrCreate([
            'user_id' => $actor->id,
            'server_id' => $invite->server_id,
        ], [
            'left_at' => null,
        ]);

        if ($membership->wasRecentlyCreated) {
            $membership->update(['nickname' => $actor->name]);
        }

        return $membership;
    }
}
