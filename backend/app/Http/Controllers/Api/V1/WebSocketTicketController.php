<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Gateway\WebSocketTicketStore;
use Illuminate\Http\JsonResponse;
use Throwable;

class WebSocketTicketController extends Controller
{
    public function __invoke(WebSocketTicketStore $tickets): JsonResponse
    {
        try {
            $user = auth()->user();

            $ticket = $tickets->issue(
                $user->id,
                $user->activeServers()->pluck('servers.id')->toArray(),
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'Could not create ticket'], 500);
        }

        return response()->json(['ticket' => $ticket]);
    }
}
