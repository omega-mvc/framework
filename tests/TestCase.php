<?php

namespace Tests;

use Omega\Database\ConnectionInterface;
use Omega\Database\DatabaseManager;
use Omega\Database\Schema\Schema;
use Omega\Database\Schema\SchemaConnection;
use Omega\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var array<string, mixed> Database connection environment variables */
    protected array $env;

    /** @var ConnectionInterface Main PDO connection instance for tests */
    protected ConnectionInterface $pdo;

    /** @var SchemaConnection Schema-level connection instance */
    protected SchemaConnection $pdoSchema;

    /** @var Schema Schema instance for database operations and teardown */
    protected Schema $schema;

    /** @var DatabaseManager Database manager instance for executing queries */
    protected DatabaseManager $db;

    /** @var string Driver currently connected (empty string when none) */
    protected string $engine = '';

    /** @var string|null Temporary SQLite file used by the current test */
    protected ?string $sqlitePath = null;

    protected function tearDown(): void
    {
        if (!isset($this->app)) {
            return;
        }

        parent::tearDown();
    }
}
