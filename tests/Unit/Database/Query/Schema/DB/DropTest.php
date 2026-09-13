<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\DB;

use Omega\Database\ConnectionInterface;
use Omega\Database\Schema\DB\Drop;
use Omega\Database\Schema\SchemaConnection;

covers(Drop::class);

beforeEach(function (): void {
    $this->pdo       = $this->createStub(ConnectionInterface::class);
    $this->pdoSchema = $this->createStub(SchemaConnection::class);
});

test('it can generate create database', function (): void {
    $schema = new Drop('test', $this->pdoSchema);

    expect($schema->__toString())->toEqual(
        'DROP DATABASE test;'
    );
});

test('it can generate create database if exists', function (): void {
    $schema = new Drop('test', $this->pdoSchema);

    expect($schema->ifExists(true)->__toString())->toEqual(
        'DROP DATABASE IF EXISTS test;'
    );
});

test('it can generate create database if exists false', function (): void {
    $schema = new Drop('test', $this->pdoSchema);

    expect($schema->ifExists(false)->__toString())->toEqual(
        'DROP DATABASE IF NOT EXISTS test;'
    );
});

test('it can generate create database if not exists', function (): void {
    $schema = new Drop('test', $this->pdoSchema);

    expect($schema->ifNotExists(true)->__toString())->toEqual(
        'DROP DATABASE IF NOT EXISTS test;'
    );
});

test('it can generate create database if not exists false', function (): void {
    $schema = new Drop('test', $this->pdoSchema);

    expect($schema->ifNotExists(false)->__toString())->toEqual(
        'DROP DATABASE IF EXISTS test;'
    );
});