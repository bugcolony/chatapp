<?php

namespace Database\Seeders;

use App\Services\DemoFixtureManager;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoFixturesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(DemoFixtureManager $fixtures): void
    {
        $fixtures->provision();
    }
}
