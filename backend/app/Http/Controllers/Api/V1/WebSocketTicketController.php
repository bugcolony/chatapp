<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Str;

class WebSocketTicketController extends Controller
{
    public function __invoke()
    {
        try {
            $ticket = Str::random(32);
            $payload = json_encode([
                'id' => auth()->user()->id,
                'serverSubscriptions' => auth()->user()->activeServers()->pluck('servers.id')->toArray(),
            ], JSON_THROW_ON_ERROR);

            return Redis::connection('realtime')
                ->client()
                ->setex("ticket:$ticket", 60, $payload)
                ? response()->json(['ticket' => $ticket])
                : throw new Exception('Unable to set WS ticket in redis.');
        } catch (Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());

            return response()->json(['error' => 'Could not create ticket'], 500);
        }
    }
}
