<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Invite\CreateServerInvite;
use App\Actions\Invite\JoinServerWithInvite;
use App\Data\Invite\CreateInviteData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Invite\StoreServerInviteRequest;
use App\Models\Server;
use App\Models\ServerInvite;
use Exception;
use Gate;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ServerInviteController extends Controller
{
    public function store(Server $server, StoreServerInviteRequest $request)
    {
        Gate::authorize('store', [ServerInvite::class, $server]);

        try {
            $invite = new CreateServerInvite()
                ->execute($server, new CreateInviteData(
                    userId: auth()->user()->id,
                    serverId: $server->id,
                    maxUses: $request->validated('max_uses'),
                    expiresAt: $request->validated('expires_at')
                ));

            if ($invite) {
                return $invite->toResource();
            }

            throw new RuntimeException('Invite could not be created');
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Could not create invite at this time'], 500);
        }
    }

    public function show(string $code): JsonResource
    {
        $invite = ServerInvite::query()->valid()->where('code', $code)->with('server')->firstOrFail();

        return $invite->toResource();
    }

    public function join(string $code)
    {
        try {
            $invite = ServerInvite::query()->valid()->where('code', $code)->with('server')->firstOrFail();

            return new JoinServerWithInvite()->execute(auth()->user(), $invite)->toResource();
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Could not join server at this time'], 500);
        }
    }
}
