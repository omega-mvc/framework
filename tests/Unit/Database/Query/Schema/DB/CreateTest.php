<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\DB;

use Omega\Database\ConnectionInterface;
use Omega\Database\Schema\DB\Create;
use Omega\Database\Schema\SchemaConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Create::class)]
final class CreateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo       = $this->createStub(ConnectionInterface::class);
        $this->pdoSchema = $this->createStub(SchemaConnection::class);
    }

    public function testItCanGenerateCreateDatabase(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE test;',
            $schema->__toString()
        );
    }

    public function testItCanGenerateCreateDatabaseIfExists(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF EXISTS test;',
            $schema->ifExists(true)->__toString()
        );
    }

    public function testItCanGenerateCreateDatabaseIfExistsFalse(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF NOT EXISTS test;',
            $schema->ifExists(false)->__toString()
        );
    }

    public function testItCanGenerateCreateDatabaseIfNotExists(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF NOT EXISTS test;',
            $schema->ifNotExists(true)->__toString()
        );
    }

    public function testItCanGenerateCreateDatabaseIfNotExistsFalse(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF EXISTS test;',
            $schema->ifNotExists(false)->__toString()
        );
    }
}
