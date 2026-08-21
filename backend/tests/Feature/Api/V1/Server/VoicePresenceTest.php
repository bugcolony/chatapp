<?php

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\Member;
use App\Models\Server;
use App\Models\User;
use App\Services\RTC\VoiceChannelPresence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function voiceServer(): array
{
    $user = User::factory()->create();
    $server = Server::factory()->for($user, 'owner')->create();

    Member::factory()->for($user)->for($server)->create();
    Sanctum::actingAs($user);

    return [$user, $server];
}

test('only voice channels of the server are looked up', function () {
    [, $server] = voiceServer();

    $voice = Channel::factory()->for($server)->create(['type' => ChannelType::Voice]);
    Channel::factory()->for($server)->create(['type' => ChannelType::Text]);

    $otherServer = Server::factory()->create();
    Channel::factory()->for($otherServer)->create(['type' => ChannelType::Voice]);

    $presence = Mockery::mock(VoiceChannelPresence::class);
    $presence->shouldReceive('snapshot')
        ->once()
        ->with($voice->id)
        ->andReturn([$voice->id => [7, 12]]);

    $this->instance(VoiceChannelPresence::class, $presence);

    $this->getJson("/api/v1/servers/{$server->id}/voice-presence")
        ->assertOk()
        ->assertExactJson(['channels' => [(string) $voice->id => [7, 12]]]);
});

test('a server with no voice channels never touches the presence store', function () {
    [, $server] = voiceServer();

    Channel::factory()->for($server)->create(['type' => ChannelType::Text]);

    $presence = Mockery::mock(VoiceChannelPresence::class);
    $presence->shouldNotReceive('snapshot');

    $this->instance(VoiceChannelPresence::class, $presence);

    $response = $this->getJson("/api/v1/servers/{$server->id}/voice-presence")->assertOk();

    expect($response->getContent())->toBe('{"channels":{}}');
});

test('empty presence serialises as an object rather than an array', function () {
    [, $server] = voiceServer();

    $voice = Channel::factory()->for($server)->create(['type' => ChannelType::Voice]);

    $presence = Mockery::mock(VoiceChannelPresence::class);
    $presence->shouldReceive('snapshot')->once()->with($voice->id)->andReturn([]);

    $this->instance(VoiceChannelPresence::class, $presence);

    $response = $this->getJson("/api/v1/servers/{$server->id}/voice-presence")->assertOk();

    expect($response->getContent())->toBe('{"channels":{}}');
});

test('a presence store outage degrades instead of failing the channel list', function () {
    [, $server] = voiceServer();

    Channel::factory()->for($server)->create(['type' => ChannelType::Voice]);

    $presence = Mockery::mock(VoiceChannelPresence::class);
    $presence->shouldReceive('snapshot')->once()->andThrow(new RuntimeException('redis down'));

    $this->instance(VoiceChannelPresence::class, $presence);

    $this->getJson("/api/v1/servers/{$server->id}/voice-presence")
        ->assertStatus(503);
});

test('a non member cannot read voice presence', function () {
    $server = Server::factory()->create();
    Channel::factory()->for($server)->create(['type' => ChannelType::Voice]);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/servers/{$server->id}/voice-presence")
        ->assertForbidden();
});
