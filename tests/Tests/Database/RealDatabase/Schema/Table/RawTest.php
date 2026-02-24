<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Raw;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Database\AbstractTestDatabase;

#[CoversClass(Raw::class)]
final class RawTest extends AbstractTestDatabase
{
    protected function setUp(): void
    {
        $this->createConnection();
    }

    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGenerateCreateDatabase()
    {
        $schema = new Raw('CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )', $this->pdoSchema);

        $this->assertTrue($schema->execute());
    }
}
