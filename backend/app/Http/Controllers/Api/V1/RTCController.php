<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Channel\BroadcastVoiceChannelEvent;
use App\Exceptions\InvalidWebhookSignature;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RTCController extends Controller
{
    public function __invoke(Request $request, BroadcastVoiceChannelEvent $action): JsonResponse
    {
        try {
            $action->execute(
                $request->header('Authorization', ''),
                $request->getContent(),
            );
        } catch (InvalidWebhookSignature $e) {
            Log::warning('LiveKit webhook rejected', ['reason' => $e->getMessage()]);

            return response()->json(['received' => false], Response::HTTP_UNAUTHORIZED);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['received' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['received' => true]);
    }
}
