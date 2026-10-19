<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\Table;

use Omega\Database\ConnectionInterface;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\Table\Drop;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Drop::class)]
final class DropTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo       = $this->createStub(ConnectionInterface::class);
        $this->pdoSchema = $this->createStub(SchemaConnection::class);
    }

    public function testItCanGenerateCreateDatabase(): void
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE testing_db.test;',
            $schema->__toString()
        );
    }

    public function testItCanGenerateCreateDatabaseIfExists(): void
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE IF EXISTS testing_db.test;',
            $schema->ifExists(true)->__toString()
        );
    }

    public function testItCanGenerateCreateDatabaseIfExistsFalse(): void
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE IF NOT EXISTS testing_db.test;',
            $schema->ifExists(false)->__toString()
        );
    }

    public function testItCanGenerateCreateDatabaseIfNotExists(): void
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE IF NOT EXISTS testing_db.test;',
            $schema->ifNotExists(true)->__toString()
        );
    }

    public function testItCanGenerateCreateDatabaseIfNotExistsFalse(): void
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE IF EXISTS testing_db.test;',
            $schema->ifNotExists(false)->__toString()
        );
    }
}
