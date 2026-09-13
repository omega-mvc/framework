<?php

declare(strict_types=1);

namespace Tests\Database\Query\Schema\Table;

use Omega\Database\ConnectionInterface;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\Table\Column;
use Omega\Database\Schema\Table\Create;

covers(Column::class);
covers(Create::class);

beforeEach(function (): void {
    $this->pdo       = $this->createStub(ConnectionInterface::class);
    $this->pdoSchema = $this->createStub(SchemaConnection::class);
});

test('it can generate query using add column', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema->addColumn()->raw('PersonID int');
    $schema->addColumn()->raw('LastName varchar(255)');
    $schema->primaryKey('PersonID');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )'
    );
});

test('it can generate query using with multy primery key', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema->addColumn()->raw('PersonID int');
    $schema->addColumn()->raw('LastName varchar(255)');
    $schema->primaryKey('PersonID');
    $schema->primaryKey('LastName');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID, LastName) )'
    );
});

test('it can generate query using add column without primery key', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema->addColumn()->raw('PersonID int');
    $schema->addColumn()->raw('LastName varchar(255)');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255) )'
    );
});

test('it can generate query using add column with unique', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema->addColumn()->raw('PersonID int');
    $schema->addColumn()->raw('LastName varchar(255)');
    $schema->unique('PersonID');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), UNIQUE (PersonID) )'
    );
});

test('it can generate query using add column with multy unique', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema->addColumn()->raw('PersonID int');
    $schema->addColumn()->raw('LastName varchar(255)');
    $schema->unique('PersonID');
    $schema->unique('LastName');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), UNIQUE (PersonID, LastName) )'
    );
});

test('it can generate query using columns', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema->columns([
        new Column()->raw('PersonID int'),
        new Column()->raw('LastName varchar(255)'),
    ]);
    $schema->primaryKey('PersonID');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )'
    );
});

test('it can generate query', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema('PersonID')->int();
    $schema('LastName')->varchar(255);
    $schema->primaryKey('PersonID');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) )'
    );
});

test('it can generate default constraint', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema('PersonID')->int()->unsigned()->default(1);
    $schema('LastName')->varchar(255)->default('-');
    $schema('sufix')->varchar(15)->defaultNull();
    $schema->primaryKey('PersonID');

    expect($schema->__toString())->toEqual(
        "CREATE TABLE testing_db.test ( PersonID int UNSIGNED DEFAULT 1, LastName varchar(255) DEFAULT '-', "
        . "sufix varchar(15) DEFAULT NULL, PRIMARY KEY (PersonID) )"
    );
});

test('it can generate query with datatype and constrait', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema('PersonID')->int()->notNull();
    $schema('LastName')->varchar(255)->null();
    $schema->primaryKey('PersonID');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int NOT NULL, LastName varchar(255) NULL, '
        . 'PRIMARY KEY (PersonID) )'
    );
});

test('it can generate query with storage engine', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema->addColumn()->raw('PersonID int');
    $schema->addColumn()->raw('LastName varchar(255)');
    $schema->primaryKey('PersonID');
    $schema->engine(Create::INNODB);

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) ) '
        . 'ENGINE=INNODB'
    );
});

test('it can generate query with character set', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema->addColumn()->raw('PersonID int');
    $schema->addColumn()->raw('LastName varchar(255)');
    $schema->primaryKey('PersonID');
    $schema->character('utf8mb4');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) ) '
        . 'CHARACTER SET utf8mb4'
    );
});

test('it can generate query with engine store and character set', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema->addColumn()->raw('PersonID int');
    $schema->addColumn()->raw('LastName varchar(255)');
    $schema->primaryKey('PersonID');
    $schema->engine(Create::INNODB);
    $schema->character('utf8mb4');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255), PRIMARY KEY (PersonID) ) '
        . 'ENGINE=INNODB CHARACTER SET utf8mb4'
    );
});

test('it can generate query with comment', function (): void {
    $schema = new Create('testing_db', 'test', $this->pdoSchema);
    $schema('PersonID')->int();
    $schema('LastName')->varchar(255)->comment('The last name of the person associated with this ID');
    $schema->primaryKey('PersonID');

    expect($schema->__toString())->toEqual(
        'CREATE TABLE testing_db.test ( PersonID int, LastName varchar(255) COMMENT '
        . '\'The last name of the person associated with this ID\', PRIMARY KEY (PersonID) )'
    );
});