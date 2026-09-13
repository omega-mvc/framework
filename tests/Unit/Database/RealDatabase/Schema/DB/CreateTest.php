<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\DB;

use Omega\Database\Schema\DB\Create;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

covers(Create::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can generate create database', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb', 'pgsql'], 'CREATE DATABASE statement');

    $this->createConnection($engine);

    // clean up so the database can be created from scratch
    $this->dropConnection();

    $schema = new Create($this->pdoSchema->getDatabase(), $this->pdoSchema);

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());