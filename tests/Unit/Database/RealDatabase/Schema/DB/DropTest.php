<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\DB;

use Omega\Database\Schema\DB\Drop;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

covers(Drop::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can generate create database', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb', 'pgsql'], 'DROP DATABASE statement');

    $this->createConnection($engine);

    $schema = new Drop($this->pdoSchema->getDatabase(), $this->pdoSchema);

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());