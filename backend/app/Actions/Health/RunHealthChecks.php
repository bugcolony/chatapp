<?php

namespace App\Actions\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RunHealthChecks
{
    private const array REDIS_CONNECTIONS = [
        'redis_ops' => 'ops',
        'redis_realtime' => 'realtime',
    ];

    public function handle(): array
    {
        $checks = ['database' => $this->checkDatabase()];

        foreach (self::REDIS_CONNECTIONS as $label => $connection) {
            $checks[$label] = $this->checkRedis($connection);
        }

        return [
            'status' => in_array('error', $checks, true) ? 'degraded' : 'ok',
            'checks' => $checks,
        ];
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkRedis(string $connection): string
    {
        try {
            $response = Redis::connection($connection)->ping();

            return $this->redisResponded($response) ? 'ok' : 'error';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function redisResponded(mixed $response): bool
    {
        if (in_array($response, [true, 1, '1'], true)) {
            return true;
        }

        return trim(strtoupper((string) $response), "+ \r\n\t") === 'PONG';
    }
}
