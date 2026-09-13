<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Raw;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

covers(Raw::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can generate create database', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'database-qualified raw SQL');

    $this->createConnection($engine);

    $schema = new Raw(
        'CREATE TABLE ' . $this->pdoSchema->getDatabase() . '.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )',
        $this->pdoSchema
    );

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());