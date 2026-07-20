<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Preference\PinnedServersRequest;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Log;
use Mockery\Exception;

class PreferenceController extends Controller
{
    public function pinnedServers(PinnedServersRequest $request): JsonResponse
    {
        try {
            $list = $request->validated('server_ids');

            Member::query()
                ->active()
                ->where('user_id', auth()->id())
                ->update([
                    'pin_position' => count($list) > 0 ? DB::raw("CASE server_id " . implode(" ", array_map(static function ($a, $b) {
                            $pos = $a + 1;
                            return "WHEN $b THEN $pos";
                        }, array_keys($list), array_values($list))) . " ELSE NULL END") : null,
                ]);

            return response()->json(["message" => "ok"]);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());

            return response()->json(['error' => 'Unable to pin servers'], 500);
        }
    }
}
