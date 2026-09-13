<?php

declare(strict_types=1);

namespace Tests\Database;

use Omega\Database\Schema\Table\Alter;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

covers(Alter::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can update database table', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $alter = $this->schema->alter('users', function (Alter $blueprint) {
        $blueprint->column('user')->varchar(20);
        $blueprint->drop('stat');
        $blueprint->add('status')->int(3);
    });

    expect($alter->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can execute using raw query', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $raw = $this->schema->raw(
        'ALTER TABLE ' . $this->pdoSchema->getDatabase() . '.users
         MODIFY COLUMN user varchar(20),
         ADD COLUMN status int(3),
         DROP COLUMN stat'
    );

    expect($raw->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());