<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Server\CreateServer;
use App\Data\Server\CreateServerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Server\StoreServerRequest;
use App\Http\Resources\Api\V1\ServerResource;
use App\Models\Server;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServerController extends Controller
{
    public function index()
    {
        try {
            return auth()
                ->user()
                ->activeServers()
                ->select([
                    "servers.*",
                    "members.pin_position as pin_position",
                ])
                ->get()
                ->toResourceCollection(ServerResource::class);
        } catch (Throwable $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show(Server $server): JsonResource
    {
        return $server->toResource();
    }

    public function store(StoreServerRequest $request)
    {
        try {
            $server = new CreateServer()
                ->execute($request->user(), new CreateServerData($request->validated('name')));

            return new ServerResource($server);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Not possible to create server at this time.'], 500);
        }
    }
}
