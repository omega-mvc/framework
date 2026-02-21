<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\DB;

use Omega\Database\Schema\DB\Drop;
use Tests\Database\TestDatabaseQuery;

final class DropTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCanGenerateCreateDatabase()
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE test;',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExists()
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE IF EXISTS test;',
            $schema->ifExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExistsFalse()
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE IF NOT EXISTS test;',
            $schema->ifExists(false)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExists()
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE IF NOT EXISTS test;',
            $schema->ifNotExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExistsFalse()
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE IF EXISTS test;',
            $schema->ifNotExists(false)->__toString()
        );
    }
}
