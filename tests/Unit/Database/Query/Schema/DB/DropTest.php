<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\DB;

use Omega\Database\Schema\DB\Drop;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\TestDatabaseQuery;

#[CoversClass(Drop::class)]
final class DropTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCanGenerateCreateDatabase(): void
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE test;',
            $schema->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExists(): void
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE IF EXISTS test;',
            $schema->ifExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfExistsFalse(): void
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE IF NOT EXISTS test;',
            $schema->ifExists(false)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExists(): void
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE IF NOT EXISTS test;',
            $schema->ifNotExists(true)->__toString()
        );
    }

    /** @test */
    public function testItCanGenerateCreateDatabaseIfNotExistsFalse(): void
    {
        $schema = new Drop('test', $this->pdoSchema);

        $this->assertEquals(
            'DROP DATABASE IF EXISTS test;',
            $schema->ifNotExists(false)->__toString()
        );
    }
}
