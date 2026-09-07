<?php

use App\Actions\Message\AckMessage;
use App\Models\Channel;
use App\Models\ChannelRead;
use App\Models\Member;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function ackContext(int $baseline = 0): array
{
    $channel = Channel::factory()->create();
    $user = User::factory()->create();

    Member::factory()->create([
        'user_id' => $user->id,
        'server_id' => $channel->server_id,
        'baseline_message_id' => $baseline,
    ]);

    Sanctum::actingAs($user);

    return [$channel, $user];
}

it('creates a read row seeded from the membership baseline', function () {
    [$channel, $user] = ackContext(baseline: 500);
    $message = Message::factory()->inChannel($channel)->create();

    app(AckMessage::class)->execute($channel, $message);

    expect(ChannelRead::where('user_id', $user->id)->value('last_read_id'))->toBe(500);
});

it('creates a read row at the message id when it is ahead of the baseline', function () {
    [$channel, $user] = ackContext(baseline: 0);
    $message = Message::factory()->inChannel($channel)->create();

    app(AckMessage::class)->execute($channel, $message);

    expect(ChannelRead::where('user_id', $user->id)->value('last_read_id'))->toBe($message->id);
});

it('advances an existing read row', function () {
    [$channel, $user] = ackContext();
    $first = Message::factory()->inChannel($channel)->create();
    $second = Message::factory()->inChannel($channel)->create();

    app(AckMessage::class)->execute($channel, $first);
    app(AckMessage::class)->execute($channel, $second);

    expect(ChannelRead::where('user_id', $user->id)->value('last_read_id'))->toBe($second->id);
});

it('never moves an existing read row backwards', function () {
    [$channel, $user] = ackContext();
    $older = Message::factory()->inChannel($channel)->create();
    $newer = Message::factory()->inChannel($channel)->create();

    app(AckMessage::class)->execute($channel, $newer);
    app(AckMessage::class)->execute($channel, $older);

    expect(ChannelRead::where('user_id', $user->id)->value('last_read_id'))->toBe($newer->id);
});

it('ignores the baseline once a read row exists', function () {
    [$channel, $user] = ackContext(baseline: 900);
    $message = Message::factory()->inChannel($channel)->create();

    app(AckMessage::class)->execute($channel, $message);
    Member::where('user_id', $user->id)->update(['baseline_message_id' => 5000]);
    app(AckMessage::class)->execute($channel, $message);

    expect(ChannelRead::where('user_id', $user->id)->value('last_read_id'))->toBe(900);
});

it('keeps one row per user and channel', function () {
    [$channel, $user] = ackContext();
    $message = Message::factory()->inChannel($channel)->create();

    app(AckMessage::class)->execute($channel, $message);
    app(AckMessage::class)->execute($channel, $message);

    expect(ChannelRead::where('user_id', $user->id)->where('channel_id', $channel->id)->count())->toBe(1);
});

it('upserts onto a row it did not create', function () {
    [$channel, $user] = ackContext();
    $message = Message::factory()->inChannel($channel)->create();

    DB::table('channel_reads')->insert([
        'channel_id' => $channel->id,
        'user_id' => $user->id,
        'last_read_id' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(AckMessage::class)->execute($channel, $message);

    expect(ChannelRead::where('user_id', $user->id)->value('last_read_id'))->toBe($message->id);
});

it('touches channel_reads in exactly one statement so no race window exists', function () {
    [$channel] = ackContext();
    $message = Message::factory()->inChannel($channel)->create();

    $statements = [];
    DB::listen(function ($query) use (&$statements) {
        if (str_contains($query->sql, 'channel_reads')) {
            $statements[] = $query->sql;
        }
    });

    app(AckMessage::class)->execute($channel, $message);

    expect($statements)->toHaveCount(1)
        ->and($statements[0])->toContain('on conflict');
});

it('leaves the surrounding transaction usable', function () {
    [$channel, $user] = ackContext();
    $message = Message::factory()->inChannel($channel)->create();

    DB::table('channel_reads')->insert([
        'channel_id' => $channel->id,
        'user_id' => $user->id,
        'last_read_id' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::transaction(function () use ($channel, $message) {
        app(AckMessage::class)->execute($channel, $message);
    });

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
});

it('rejects a user who is not a member of the server', function () {
    $channel = Channel::factory()->create();
    $message = Message::factory()->inChannel($channel)->create();

    Sanctum::actingAs(User::factory()->create());

    expect(fn () => app(AckMessage::class)->execute($channel, $message))
        ->toThrow(RuntimeException::class);
});

it('tracks each channel separately', function () {
    [$channel, $user] = ackContext();
    $other = Channel::factory()->create(['server_id' => $channel->server_id]);

    $message = Message::factory()->inChannel($channel)->create();
    $otherMessage = Message::factory()->inChannel($other)->create();

    app(AckMessage::class)->execute($channel, $message);
    app(AckMessage::class)->execute($other, $otherMessage);

    expect(ChannelRead::where('user_id', $user->id)->where('channel_id', $channel->id)->value('last_read_id'))->toBe($message->id)
        ->and(ChannelRead::where('user_id', $user->id)->where('channel_id', $other->id)->value('last_read_id'))->toBe($otherMessage->id);
});

it('tracks each user separately', function () {
    [$channel, $user] = ackContext();
    $other = User::factory()->create();
    Member::factory()->create(['user_id' => $other->id, 'server_id' => $channel->server_id]);

    $first = Message::factory()->inChannel($channel)->create();
    $second = Message::factory()->inChannel($channel)->create();

    app(AckMessage::class)->execute($channel, $first);
    Sanctum::actingAs($other);
    app(AckMessage::class)->execute($channel, $second);

    expect(ChannelRead::where('user_id', $user->id)->value('last_read_id'))->toBe($first->id)
        ->and(ChannelRead::where('user_id', $other->id)->value('last_read_id'))->toBe($second->id);
});
