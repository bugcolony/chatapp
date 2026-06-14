<?php

use App\Actions\Health\RunHealthChecks;

test('health response includes service checks', function () {
    $this->mock(RunHealthChecks::class)
        ->shouldReceive('handle')
        ->once()
        ->andReturn([
            'status' => 'ok',
            'checks' => [
                'database' => 'ok',
                'redis_ops' => 'ok',
                'redis_realtime' => 'ok',
            ],
        ]);

    $this->getJson('/api/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'checks' => [
                'database' => 'ok',
                'redis_ops' => 'ok',
                'redis_realtime' => 'ok',
            ],
        ]);
});
