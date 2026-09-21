<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Direct\AddParticipant;
use App\Actions\Direct\CreateGroupChannel;
use App\Actions\Direct\LeaveChannel;
use App\Actions\Direct\RemoveParticipant;
use App\Enums\ChannelType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Direct\StoreGroupChannelRequest;
use App\Http\Requests\Api\V1\Direct\StoreGroupParticipantRequest;
use App\Http\Resources\Api\V1\DirectMessageChannelResource;
use App\Models\Channel;
use App\Models\ChannelRead;
use App\Models\User;
use Gate;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Exceptions\GroupOperationException;
use Throwable;

class DirectMessageController extends Controller
{
    /**
     * @throws Throwable
     */
    public function index(): ResourceCollection
    {
        $user = auth()->user();

        return DirectMessageChannelResource::collection($user
            ->directMessageChannels()
            ->withPivot('hidden_before_message_id')
            ->with('participants')
            ->select('channels.*')
            ->addSelect(['last_read_id' => ChannelRead::query()
                ->select('last_read_id')
                ->whereColumn('channel_reads.channel_id', 'channels.id')
                ->where('channel_reads.user_id', $user->id)])
            ->get());
    }

    public function open(User $friend): DirectMessageChannelResource
    {
        $user = auth()->user();

        abort_if($user->is($friend), 404);

        $channel = Channel::query()
            ->where('type', ChannelType::DIRECT_MESSAGE)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $friend->id))
            ->firstOrFail();

        $channel->participants()->updateExistingPivot($user->id, ['hidden_before_message_id' => null]);

        return DirectMessageChannelResource::make(
            $channel->load('participants')
        );
    }

    public function store(StoreGroupChannelRequest $request, CreateGroupChannel $action)
    {
        try {
            return DirectMessageChannelResource::make($action->execute(
                auth()->user(),
                $request->validated('participant_ids'),
            ));
        } catch (GroupOperationException $th) {
            return response()->json(['message' => $th->getMessage()], $th->status());
        } catch (Throwable $th) {
            report($th);

            return response()->json(['error' => 'Could not create group'], 500);
        }
    }

    public function storeParticipant(Channel $channel, StoreGroupParticipantRequest $request, AddParticipant $action)
    {
        Gate::authorize('addParticipant', [Channel::class, $channel]);

        $participant = User::findOrFail($request->validated('user_id'));

        try {
            return DirectMessageChannelResource::make(
                $action->execute($channel, auth()->user(), $participant)
            );
        } catch (GroupOperationException $th) {
            return response()->json(['message' => $th->getMessage()], $th->status());
        } catch (Throwable $th) {
            report($th);

            return response()->json(['error' => 'Could not add the participant'], 500);
        }
    }

    public function destroyParticipant(Channel $channel, User $user, RemoveParticipant $action)
    {
        Gate::authorize('removeParticipant', [Channel::class, $channel]);

        try {
            return DirectMessageChannelResource::make($action->execute($channel, $user));
        } catch (GroupOperationException $th) {
            return response()->json(['message' => $th->getMessage()], $th->status());
        } catch (Throwable $th) {
            report($th);

            return response()->json(['error' => 'Could not remove the participant'], 500);
        }
    }

    public function leave(Channel $channel, LeaveChannel $action)
    {
        Gate::authorize('leave', [Channel::class, $channel]);

        try {
            $action->execute($channel, auth()->user());

            return response()->noContent();
        } catch (GroupOperationException $th) {
            return response()->json(['message' => $th->getMessage()], $th->status());
        } catch (Throwable $th) {
            report($th);

            return response()->json(['error' => 'Could not leave the group'], 500);
        }
    }
}
