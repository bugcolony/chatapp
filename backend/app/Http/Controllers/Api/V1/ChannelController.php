<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Channel\StoreChannelRequest;
use App\Http\Resources\Api\V1\ChannelResource;
use App\Models\Channel;
use App\Models\Server;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChannelController extends Controller
{
    public function index(Server $server)
    {
        try {
            return $server->channels()->get()->toResourceCollection(ChannelResource::class);
        } catch (Throwable $th) {
            Log::error($th->getMessage(), $th->getTrace());

            return response()->json(['error' => 'Could not retrieve server channel list'], 500);
        }
    }

    public function store(Server $server, StoreChannelRequest $request)
    {
        try {
            return $server->channels()->create($request->validated())->toResource();
        } catch (Throwable $th) {
            Log::error($th->getMessage(), $th->getTrace());

            return response()->json(['error' => 'Could not create channel'], 500);
        }
    }

    public function show(Channel $channel): JsonResource
    {
        return $channel->toResource();
    }
}
