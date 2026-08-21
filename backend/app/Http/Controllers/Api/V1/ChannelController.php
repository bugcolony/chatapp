<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Channel\DeleteChannel;
use App\Enums\ChannelType;
use App\Events\ChannelCreated;
use App\Events\ChannelUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Channel\StoreChannelRequest;
use App\Http\Requests\Api\V1\Channel\UpdateChannelRequest;
use App\Http\Resources\Api\V1\ChannelResource;
use App\Models\Channel;
use App\Models\Server;
use Gate;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChannelController extends Controller
{
    public function index(Server $server)
    {
        try {
            return $server->channels()->with(['voiceTextChannel'])->get()->toResourceCollection(ChannelResource::class);
        } catch (Throwable $th) {
            Log::error($th->getMessage());

            return response()->json(['error' => 'Could not retrieve server channel list'], 500);
        }
    }

    public function show(Channel $channel): JsonResource
    {
        return $channel->toResource();
    }

    public function store(Server $server, StoreChannelRequest $request)
    {
        Gate::authorize('store', [Channel::class, $server]);

        try {
            $channel = DB::transaction(function () use ($request, $server) {
                $channel = $server->channels()->create($request->validated());

                if ($channel->type === ChannelType::Voice) {
                    $voiceTextChannel = $channel->children()->create([
                        'server_id' => $server->id,
                        'name' => "$channel->name chat",
                        'type' => ChannelType::VoiceText,
                    ]);

                    $channel->setRelation('voiceTextChannel', $voiceTextChannel);
                }

                return $channel;
            });


            ChannelCreated::dispatch($channel);

            return $channel->toResource();
        } catch (Throwable $th) {
            Log::error($th->getMessage());

            return response()->json(['error' => 'Could not create channel'], 500);
        }
    }

    public function update(Channel $channel, UpdateChannelRequest $request)
    {
        Gate::authorize('update', [Channel::class, $channel]);

        try {
            $channel->update($request->validated());
            $channel->refresh();

            ChannelUpdated::dispatch($channel);

            return $channel->toResource();
        } catch (Throwable $th) {
            Log::error($th->getMessage());

            return response()->json(['error' => 'Could not update channel'], 500);
        }
    }

    public function destroy(Channel $channel, DeleteChannel $deleteChannel)
    {
        Gate::authorize('destroy', [Channel::class, $channel]);

        try {
            $deleteChannel->execute($channel);

            return response()->noContent();
        } catch (Throwable $th) {
            Log::error($th->getMessage());

            return response()->json(['error' => 'Could not delete channel'], 500);
        }
    }
}
