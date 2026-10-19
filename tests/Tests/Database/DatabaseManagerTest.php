<?php

declare(strict_types=1);

namespace Tests\Database;

use Omega\Database\DatabaseManager;
use Omega\Database\Exceptions\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

#[CoversClass(DatabaseManager::class)]
#[CoversClass(InvalidConfigurationException::class)]
final class DatabaseManagerTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanSetDefaultConnection(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();

        $db = new DatabaseManager([
            'testing' => $this->env,
        ]);

        $db->setDefaultConnection($this->pdo);
        $this->assertTrue($db->query('SELECT * FROM users')->execute());
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetConnection(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();

        $db = new DatabaseManager([
            'testing' => $this->env,
        ]);

        $this->assertTrue($db->connection('testing')->query('SELECT * FROM users')->execute());
    }

    public function testItCanThrowExceptionWhenConnectionNotConfigure(): void
    {
        $db = new DatabaseManager([
            'invalid' => null,
        ]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageIsOrContains('Database connection [invalid] not configured.');

        $this->assertTrue($db->connection('invalid')->query('SELECT * FROM users')->execute());
    }
}
