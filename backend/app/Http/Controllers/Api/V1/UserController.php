<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\DeleteAccount;
use App\Actions\User\UpdateUserProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteAccountRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\AuthUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserController extends Controller
{
    public function update(UpdateUserRequest $request, UpdateUserProfile $action)
    {
        try {
            $user = $action->execute(
                auth()->user(),
                $request->safe()->except(['avatar', 'remove_avatar']),
                $request->safe()->input('remove_avatar', false),
                $request->safe()->file('avatar')
            );

            return AuthUserResource::make($user);
        } catch (Throwable $e) {
            Log::error($e);

            return response()->json(['message' => 'Failed to update user'], 500);
        }
    }

    public function destroy(DeleteAccountRequest $request, DeleteAccount $action): JsonResponse
    {
        try {
            $action->execute($request->user());
        } catch (Throwable $e) {
            Log::error($e);

            return response()->json(['message' => 'Failed to close account'], 500);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Account closed']);
    }
}
