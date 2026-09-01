<?php

namespace App\Actions\User;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CompleteOnboarding
{
    public function execute(User $user, string $username): User
    {
        try {
            $user->update([
                'username' => $username,
                'name' => $username,
                'onboarded_at' => now(),
            ]);
        } catch (QueryException $e) {
            if (! $this->isIntegrityConstraintViolation($e)) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'username' => 'The username has already been taken.',
            ]);
        }

        Member::where('user_id', $user->id)->update(['nickname' => $username]);

        return $user->refresh();
    }

    private function isIntegrityConstraintViolation(QueryException $e): bool
    {
        return str_starts_with((string) $e->getCode(), '23');
    }
}
