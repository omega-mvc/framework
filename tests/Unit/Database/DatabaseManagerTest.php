<?php

declare(strict_types=1);

namespace Tests\Database;

use Omega\Database\DatabaseManager;
use Omega\Database\Exceptions\InvalidConfigurationException;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

covers(DatabaseManager::class);
covers(InvalidConfigurationException::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can set default connection', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();

    $db = new DatabaseManager([
        'testing' => $this->env,
    ]);

    $db->setDefaultConnection($this->pdo);
    expect($db->query('SELECT * FROM users')->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can get connection', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();

    $db = new DatabaseManager([
        'testing' => $this->env,
    ]);

    expect($db->connection('testing')->query('SELECT * FROM users')->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can throw exception when connection not configure', function (): void {
    $db = new DatabaseManager([
        'invalid' => null,
    ]);

    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessageIsOrContains('Database connection [invalid] not configured.');

    $this->assertTrue($db->connection('invalid')->query('SELECT * FROM users')->execute());
});