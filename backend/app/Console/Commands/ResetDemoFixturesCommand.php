<?php

namespace App\Console\Commands;

use App\Services\DemoFixtureManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset')]
#[Description('Remove all demo activity and restore the predictable demo fixtures')]
class ResetDemoFixturesCommand extends Command
{
    public function handle(DemoFixtureManager $fixtures): int
    {
        $fixtures->reset();

        $this->components->info(sprintf(
            'Demo fixtures reset: %d users and %d servers restored.',
            count(DemoFixtureManager::USERS),
            count(DemoFixtureManager::SERVERS),
        ));

        return self::SUCCESS;
    }
}
