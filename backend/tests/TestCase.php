<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $connection = getenv('DB_CONNECTION');
        $database = getenv('DB_DATABASE');

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Refusing to run tests against DB_CONNECTION=%s DB_DATABASE=%s. Tests require in-memory SQLite.',
                $connection === false ? '<unset>' : $connection,
                $database === false ? '<unset>' : $database,
            ));
        }

        parent::setUp();
    }
}
