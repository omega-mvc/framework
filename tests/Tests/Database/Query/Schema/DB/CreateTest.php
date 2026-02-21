<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\DB;

use Omega\Database\Schema\DB\Create;
use Tests\Database\TestDatabaseQuery;

final class CreateTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCanGenerateCreateDatabase()
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE test;',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExists()
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF EXISTS test;',
            $schema->ifExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExistsFalse()
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF NOT EXISTS test;',
            $schema->ifExists(false)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExists()
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF NOT EXISTS test;',
            $schema->ifNotExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExistsFalse()
    {
        $schema = new Create('test', $this->pdoSchema);

        $this->assertEquals(
            'CREATE DATABASE IF EXISTS test;',
            $schema->ifNotExists(false)->__toString()
        );
    }
}
