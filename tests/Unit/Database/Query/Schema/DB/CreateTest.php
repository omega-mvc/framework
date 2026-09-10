<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\DB;

use Omega\Database\Schema\DB\Create;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\TestDatabaseQuery;

#[CoversClass(Create::class)]
final class CreateTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCanGenerateCreateDatabase(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE test;',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExists(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF EXISTS test;',
            $schema->ifExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExistsFalse(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF NOT EXISTS test;',
            $schema->ifExists(false)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExists(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF NOT EXISTS test;',
            $schema->ifNotExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExistsFalse(): void
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF EXISTS test;',
            $schema->ifNotExists(false)->__toString()
        );
    }
}
