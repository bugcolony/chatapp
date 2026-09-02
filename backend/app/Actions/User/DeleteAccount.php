<?php

namespace App\Actions\User;

use App\Models\ChannelPermissionOverride;
use App\Models\Member;
use App\Models\ServerInvite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteAccount
{
    public const string DISPLAY_NAME = 'Deleted User';

    /**
     * @throws Throwable
     */
    public function execute(User $user): void
    {
        DB::transaction(static function () use ($user) {
            $user->deleteAvatar();

            $user->socialAccounts()->delete();
            $user->tokens()->delete();
            $user->serverRoles()->detach();

            ChannelPermissionOverride::query()->where('user_id', $user->id)->delete();
            ServerInvite::query()->where('created_by', $user->id)->delete();

            Member::query()
                ->where('user_id', $user->id)
                ->update(['nickname' => self::DISPLAY_NAME]);

            Member::query()
                ->where('user_id', $user->id)
                ->whereNull('left_at')
                ->update(['left_at' => now()]);

            $user->forceFill([
                'name' => self::DISPLAY_NAME,
                'username' => "deleted-{$user->id}",
                'email' => "deleted-{$user->id}@deleted.invalid",
                'email_verified_at' => null,
                'password' => null,
                'remember_token' => null,
                'closed_at' => now(),
            ])->save();
        });
    }
}
