<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\DB;

use Omega\Database\ConnectionInterface;
use Omega\Database\Schema\DB\Create;
use Omega\Database\Schema\SchemaConnection;

covers(Create::class);

beforeEach(function (): void {
    $this->pdo       = $this->createStub(ConnectionInterface::class);
    $this->pdoSchema = $this->createStub(SchemaConnection::class);
});

test('it can generate create database', function (): void {
    $schema = new Create('test', $this->pdoSchema);

    expect($schema->__toString())->toEqual(
        'CREATE DATABASE test;'
    );
});

test('it can generate create database if exists', function (): void {
    $schema = new Create('test', $this->pdoSchema);

    expect($schema->ifExists(true)->__toString())->toEqual(
        'CREATE DATABASE IF EXISTS test;'
    );
});

test('it can generate create database if exists false', function (): void {
    $schema = new Create('test', $this->pdoSchema);

    expect($schema->ifExists(false)->__toString())->toEqual(
        'CREATE DATABASE IF NOT EXISTS test;'
    );
});

test('it can generate create database if not exists', function (): void {
    $schema = new Create('test', $this->pdoSchema);

    expect($schema->ifNotExists(true)->__toString())->toEqual(
        'CREATE DATABASE IF NOT EXISTS test;'
    );
});

test('it can generate create database if not exists false', function (): void {
    $schema = new Create('test', $this->pdoSchema);

    expect($schema->ifNotExists(false)->__toString())->toEqual(
        'CREATE DATABASE IF EXISTS test;'
    );
});