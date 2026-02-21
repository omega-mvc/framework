<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\Table;

use Omega\Database\Schema\Table\Drop;
use Tests\Database\TestDatabaseQuery;

final class DropTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCanGenerateCreateDatabase()
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE testing_db.test;',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExists()
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE IF EXISTS testing_db.test;',
            $schema->ifExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExistsFalse()
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE IF NOT EXISTS testing_db.test;',
            $schema->ifExists(false)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExists()
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE IF NOT EXISTS testing_db.test;',
            $schema->ifNotExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExistsFalse()
    {
        $schema = new Drop('testing_db', 'test', $this->pdoSchema);

        $this->assertEquals(
            'DROP TABLE IF EXISTS testing_db.test;',
            $schema->ifNotExists(false)->__toString()
        );
    }
}
