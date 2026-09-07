<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'pgsql' || $database !== 'chat_test') {
            throw new RuntimeException(sprintf(
                'Refusing to run tests against connection=%s database=%s. Tests require the pgsql chat_test database.',
                $connection,
                $database === '' ? '<unset>' : $database,
            ));
        }
    }
}
