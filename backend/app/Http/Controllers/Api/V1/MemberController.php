<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\Member;
use App\Models\Server;
use Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberController extends Controller
{
    public function index(Server $server): JsonResponse|ResourceCollection
    {
        try {
            return $server->members()->with('user')->get()->toResourceCollection(MemberResource::class);
        } catch (Throwable $th) {
            Log::error($th->getMessage(), $th->getTrace());

            return response()->json(['error' => 'Server not found'], 404);
        }
    }

    public function destroy(Server $server): JsonResponse
    {
        $membership = Member::query()->where([
            'user_id' => auth()->id(),
            'server_id' => $server->id,
        ])->firstOrFail();

        Gate::authorize('destroy', [Member::class, $server]);

        $membership->update(['left_at' => now()]);

        return response()->json(['message' => 'You have left the server']);
    }
}
