<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\Table;

use Omega\Database\ConnectionInterface;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\Table\Raw;

covers(Raw::class);

beforeEach(function (): void {
    $this->pdo       = $this->createStub(ConnectionInterface::class);
    $this->pdoSchema = $this->createStub(SchemaConnection::class);
});

test('it can generate query using add column', function (): void {
    $schema = new Raw(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )',
        $this->pdoSchema
    );

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )'
    );
});