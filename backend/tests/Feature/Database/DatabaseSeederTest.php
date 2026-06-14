<?php

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('default seeding creates roles without fixture users', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0)
        ->and(Role::query()->pluck('name')->all())->toEqualCanonicalizing([
            SystemRole::Admin->value,
            SystemRole::User->value,
        ]);
});

test('default seeding may be run repeatedly', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Role::query()->count())->toBe(2);
});
