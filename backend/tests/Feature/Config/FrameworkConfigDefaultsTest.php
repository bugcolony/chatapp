<?php

use Illuminate\Support\Str;

test('laravel 13 cache serialization is hardened', function () {
    expect(config('cache.serializable_classes'))->toBeFalse();
});

test('laravel 13 session cookie uses the snake case default', function () {
    expect(config('session.cookie'))->toBe(
        Str::snake((string) config('app.name')).'_session'
    );
});

test('sessions are configured for thirty days', function () {
    expect(config('session.lifetime'))->toBe(43200);
});
