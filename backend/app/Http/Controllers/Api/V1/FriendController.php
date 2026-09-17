<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Friend\AcceptFriendRequest;
use App\Actions\Friend\SendFriendRequest;
use App\Enums\FriendStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Friend\StoreFriendRequest;
use App\Http\Resources\Api\V1\FriendResource;
use App\Models\Friend;
use App\Models\User;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class FriendController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'friends' => FriendResource::collection($user->friends()->orderBy('name')->get()),
            'incoming' => FriendResource::collection($user->incomingFriendRequests()->latest('friends.created_at')->get()),
        ]);
    }

    public function store(StoreFriendRequest $request, SendFriendRequest $action): JsonResponse
    {
        $friend = User::query()->where('username', $request->validated('username'))->firstOrFail();

        $accepted = $action->execute(auth()->user(), $friend) === FriendStatus::FRIEND;

        return response()->json([
            'message' => $accepted ? "You're now friends." : 'Friend request sent.',
            'accepted' => $accepted,
            'friend' => FriendResource::make($friend),
        ], 201);
    }

    public function accept(User $friend, AcceptFriendRequest $action): JsonResponse
    {
        abort_unless($action->execute(auth()->user(), $friend), 404);

        return response()->json(['message' => "You're now friends."]);
    }

    /**
     * @throws Throwable
     */
    public function destroy(User $friend, RealtimeTransport $transport): JsonResponse
    {
        $user = auth()->user();

        $removed = DB::transaction(static function () use ($user, $friend): bool {
            $removed = Friend::query()
                ->between($user->id, $friend->id)
                ->whereNot('status', FriendStatus::BLOCKED)
                ->delete();

            if (! $removed) {
                return false;
            }

            Friend::query()
                ->between($friend->id, $user->id)
                ->whereNot('status', FriendStatus::BLOCKED)
                ->delete();

            return true;
        });

        abort_unless($removed, 404);

        $transport->publish(GatewayEvent::friendRemoved($user->id, $friend->id));

        return response()->json(['message' => 'removed']);
    }

    /**
     * @throws Throwable
     */
    public function block(User $friend, RealtimeTransport $transport): JsonResponse
    {
        $user = auth()->user();

        abort_if($user->is($friend), 422, "You can't block yourself.");

        DB::transaction(static function () use ($user, $friend): void {
            Friend::query()->updateOrCreate(
                ['user_id' => $user->id, 'friend_id' => $friend->id],
                ['status' => FriendStatus::BLOCKED],
            );

            Friend::query()
                ->between($friend->id, $user->id)
                ->whereNot('status', FriendStatus::BLOCKED)
                ->delete();
        });

        $transport->publish(GatewayEvent::friendRemoved($user->id, $friend->id));

        return response()->json(['message' => 'blocked']);
    }

    public function unblock(User $friend): JsonResponse
    {
        $unblocked = Friend::query()
            ->between(auth()->id(), $friend->id)
            ->where('status', FriendStatus::BLOCKED)
            ->delete();

        abort_unless($unblocked, 404);

        return response()->json(['message' => 'unblocked']);
    }
}
