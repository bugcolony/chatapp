<?php

use App\Models\Member;
use App\Models\Server;
use App\Models\ServerRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);
function createMemberFixtures(): array
{
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $owner->id]);

    $baseRole = ServerRole::factory()->system()->create([
        'server_id' => $server->id,
        'permissions' => 0,
    ]);

    $server->update(['base_role_id' => $baseRole->id]);

    Member::factory()->create([
        'user_id' => $owner->id,
        'server_id' => $server->id,
    ]);

    Member::factory()->create([
        'user_id' => $member->id,
        'server_id' => $server->id,
    ]);

    return compact('owner', 'member', 'server', 'baseRole');
}

test('server members can leave the server', function () {
    $fixtures = createMemberFixtures();

    actingAs($fixtures['member']);

    $response = $this->post("/api/v1/servers/{$fixtures['server']->id}/leave", [], ['Accept' => 'application/json']);
    $response->assertSuccessful();
});

test('server owner cant leave the server', function () {
    $fixtures = createMemberFixtures();

    actingAs($fixtures['owner']);

    $response = $this->post("/api/v1/servers/{$fixtures['server']->id}/leave", [], ['Accept' => 'application/json']);
    $response->assertForbidden();
});
