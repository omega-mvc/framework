<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\Table;

use Omega\Database\ConnectionInterface;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\Table\Drop;

covers(Drop::class);

beforeEach(function (): void {
    $this->pdo       = $this->createStub(ConnectionInterface::class);
    $this->pdoSchema = $this->createStub(SchemaConnection::class);
});

test('it can generate create database', function (): void {
    $schema = new Drop('testing_db', 'test', $this->pdoSchema);

    expect($schema->__toString())->toEqual(
        'DROP TABLE testing_db.test;'
    );
});

test('it can generate create database if exists', function (): void {
    $schema = new Drop('testing_db', 'test', $this->pdoSchema);

    expect($schema->ifExists(true)->__toString())->toEqual(
        'DROP TABLE IF EXISTS testing_db.test;'
    );
});

test('it can generate create database if exists false', function (): void {
    $schema = new Drop('testing_db', 'test', $this->pdoSchema);

    expect($schema->ifExists(false)->__toString())->toEqual(
        'DROP TABLE IF NOT EXISTS testing_db.test;'
    );
});

test('it can generate create database if not exists', function (): void {
    $schema = new Drop('testing_db', 'test', $this->pdoSchema);

    expect($schema->ifNotExists(true)->__toString())->toEqual(
        'DROP TABLE IF NOT EXISTS testing_db.test;'
    );
});

test('it can generate create database if not exists false', function (): void {
    $schema = new Drop('testing_db', 'test', $this->pdoSchema);

    expect($schema->ifNotExists(false)->__toString())->toEqual(
        'DROP TABLE IF EXISTS testing_db.test;'
    );
});