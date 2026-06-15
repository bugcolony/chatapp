<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('trusted proxy headers provide the client ip and https scheme', function () {
    Route::get('/test/proxy-headers', static fn (Request $request) => [
        'ip' => $request->ip(),
        'secure' => $request->isSecure(),
        'host' => $request->getHost(),
        'port' => $request->getPort(),
    ]);

    $this
        ->withServerVariables(['REMOTE_ADDR' => '172.20.0.10'])
        ->withHeaders([
            'X-Forwarded-For' => '203.0.113.10',
            'X-Forwarded-Host' => 'chat.example.com',
            'X-Forwarded-Port' => '443',
            'X-Forwarded-Proto' => 'https',
        ])
        ->getJson('/test/proxy-headers')
        ->assertOk()
        ->assertExactJson([
            'ip' => '203.0.113.10',
            'secure' => true,
            'host' => 'chat.example.com',
            'port' => 443,
        ]);
});
