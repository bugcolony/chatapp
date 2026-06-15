<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Message\StoreMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Channel;
use App\Models\Message;
use Gate;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class MessageController extends Controller
{
    /**
     * @throws Throwable
     */
    public function index(Channel $channel): ResourceCollection
    {
        return $channel
            ->messages()
            ->latest('id')
            ->with('author')
            ->cursorPaginate(12)
            ->toResourceCollection();
    }

    public function store(StoreMessageRequest $request, Channel $channel): MessageResource
    {
        Gate::authorize('store', [Message::class, $channel]);

        $message = $channel->messages()->create([
            ...$request->safe()->only(['content']),
            'server_id' => $channel->server_id,
            'user_id' => auth()->id(),
        ]);

        MessageCreated::dispatch($message);

        $message->load('author');

        return (new MessageResource($message))
            ->withClientId((int) $request->validated('client_id'));
    }
}
