<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\Table;

use Omega\Database\Schema\Table\Raw;
use Tests\Database\TestDatabaseQuery;

final class RawTest extends TestDatabaseQuery
{
    /** @test */
    public function testItCanGenerateQueryUsingAddColumn()
    {
        $schema = new Raw('CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )', $this->pdoSchema);

        $this->assertEquals(
            'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )',
            $schema->__toString()
        );
    }
}
