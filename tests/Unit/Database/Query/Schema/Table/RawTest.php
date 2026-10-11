<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\Table;

use Omega\Database\ConnectionInterface;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\Table\Raw;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Raw::class)]
final class RawTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo       = $this->createStub(ConnectionInterface::class);
        $this->pdoSchema = $this->createStub(SchemaConnection::class);
    }

    public function testItCanGenerateQueryUsingAddColumn(): void
    {
        $schema = new Raw(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )',
            $this->pdoSchema
        );

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )',
            $schema->__toString()
        );
    }
}
