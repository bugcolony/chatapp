<?php

namespace App\Actions\User;

use App\Models\Member;
use App\Models\User;
use App\Services\File\FileStorage;
use Illuminate\Http\UploadedFile;

class UpdateUserProfile
{
    public function execute(User $user, array $fields, bool $removeAvatar, ?UploadedFile $avatar): User
    {
        if ($avatar !== null) {
            $this->deleteAvatar($user);

            $storage = new FileStorage();

            $storedFile = $storage->storeToDisk($avatar, 'avatars');

            $user->avatar()->associate($storage->persistToDb($storedFile));
        }

        if ($removeAvatar) {
            $this->deleteAvatar($user);
        }

        $user->fill($fields);

        if ($user->isDirty()) {
            $nameChanged = $user->isDirty('name');

            $user->save();

            if ($nameChanged) {
                Member::where('user_id', $user->id)->update(['nickname' => $user->name]);
            }
        }

        return $user->refresh();
    }

    private function deleteAvatar(User $user): void
    {
        $avatar = $user->avatar;

        if ($avatar === null) {
            return;
        }

        $user->avatar()->dissociate();
        $user->save();

        $avatar->delete();
    }
}
