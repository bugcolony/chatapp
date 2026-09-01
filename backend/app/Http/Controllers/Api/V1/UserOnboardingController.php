<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\User\CompleteOnboarding;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OnboardUserRequest;
use App\Http\Resources\Api\V1\AuthUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserOnboardingController extends Controller
{
    public function __invoke(OnboardUserRequest $request, CompleteOnboarding $action): AuthUserResource|JsonResponse
    {
        $user = $request->user();

        if ($user->isOnboarded()) {
            return response()->json(['message' => 'Onboarding already completed'], 409);
        }

        try {
            return AuthUserResource::make(
                $action->execute($user, $request->validated('username')),
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Failed to complete onboarding'], 500);
        }
    }
}
