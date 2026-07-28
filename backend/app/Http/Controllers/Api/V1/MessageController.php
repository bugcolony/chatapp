<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Message\CreateMessage;
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
    public function __construct(
        private readonly CreateMessage $createMessage,
    ) {}

    /**
     * @throws Throwable
     */
    public function index(Channel $channel): ResourceCollection
    {
        return $channel
            ->messages()
            ->latest('id')
            ->with(['attachment', 'author', 'mentions'])
            ->cursorPaginate(12)
            ->toResourceCollection();
    }

    public function store(StoreMessageRequest $request, Channel $channel): MessageResource
    {
        Gate::authorize('store', [
            Message::class,
            $channel,
            (int) ($request->file('attachment')?->getSize() ?? 0),
        ]);

        $message = $this->createMessage->execute(
            channel: $channel,
            author: $request->user(),
            content: $request->validated('content'),
            upload: $request->file('attachment'),
        );

        return new MessageResource($message)
            ->withClientId((int) $request->validated('client_id'));
    }
}
