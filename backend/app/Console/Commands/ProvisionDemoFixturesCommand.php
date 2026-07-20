<?php

namespace App\Console\Commands;

use App\Services\DemoFixtureManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:provision')]
#[Description('Provision the predictable demo users, servers, channels, and messages')]
class ProvisionDemoFixturesCommand extends Command
{
    public function handle(DemoFixtureManager $fixtures): int
    {
        $fixtures->provision();

        $this->components->info(sprintf(
            'Demo fixtures provisioned: %d users, %d servers, password "%s".',
            count(DemoFixtureManager::USERS),
            count(DemoFixtureManager::SERVERS),
            DemoFixtureManager::PASSWORD,
        ));

        return self::SUCCESS;
    }
}
