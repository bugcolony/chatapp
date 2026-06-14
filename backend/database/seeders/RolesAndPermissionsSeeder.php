<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate(SystemRole::Admin->value);
        Role::findOrCreate(SystemRole::User->value);
    }
}
