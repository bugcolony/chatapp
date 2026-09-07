<?php

namespace App\Actions\Invite;

use App\Models\Member;
use App\Models\ServerInvite;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JoinServerWithInvite
{
    public function execute(User $actor, ServerInvite $invite): Member
    {
        $membership = Member::query()->updateOrCreate([
            'user_id' => $actor->id,
            'server_id' => $invite->server_id,
        ], [
            'left_at' => null,
            'baseline_message_id' => DB::table('messages')->max('id') ?? 0,
        ]);

        if ($membership->wasRecentlyCreated) {
            $membership->update(['nickname' => $actor->name]);
        }

        return $membership;
    }
}
