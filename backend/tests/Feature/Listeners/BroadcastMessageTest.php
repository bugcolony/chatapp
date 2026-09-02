<?php

use App\Enums\BroadcastOperation;
use App\Events\MessageCreated;
use App\Listeners\BroadcastMessage;
use App\Models\Message;
use App\Models\User;
use App\Services\Gateway\RealtimeTransport;
use Tests\Support\FakeTransport;

test('message created payload carries every routing field the browser filters on', function () {
    $message = new Message([
        'server_id' => 12,
        'channel_id' => 34,
        'user_id' => 78,
        'content' => 'hello',
    ]);
    $message->id = 56;
    $message->created_at = now();
    $message->setRelation('author', new User(['name' => 'ada']));
    $message->setRelation('mentions', collect());
    $message->setRelation('attachment', null);

    $transport = new FakeTransport;
    $this->app->instance(RealtimeTransport::class, $transport);

    $this->app->make(BroadcastMessage::class)->handle(new MessageCreated($message));

    $operation = $transport->sole();

    expect($operation->op)->toBe(BroadcastOperation::MESSAGE_CREATED)
        ->and($operation->target->serverId)->toBe(12)
        ->and($operation->target->channelId)->toBe(34)
        ->and($operation->target->senderId)->toBe(78)
        ->and($operation->data['id'])->toBe(56)
        ->and($operation->data['author']['name'])->toBe('ada')
        ->and($operation->data['message'])->toBe('hello');
});
