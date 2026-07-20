<?php

namespace App\Console\Commands;

use App\Services\DemoFixtureManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:remove')]
#[Description('Remove the predictable demo users and all data associated with them')]
class RemoveDemoFixturesCommand extends Command
{
    public function handle(DemoFixtureManager $fixtures): int
    {
        $fixtures->remove();

        $this->components->info('Demo fixtures removed.');

        return self::SUCCESS;
    }
}
