<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Create;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

covers(Create::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can generate create database', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL CREATE TABLE clauses');

    $this->createConnection($engine);

    $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

    $schema('id')->int(3)->notNull();
    $schema('name')->varchar(32)->notNull();
    $schema('gender')->int(1);
    $schema->primaryKey('id');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can execute query with multy primery key', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL CREATE TABLE clauses');

    $this->createConnection($engine);

    $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

    $schema('id')->int(3)->notNull();
    $schema('xid')->int(3)->notNull();
    $schema('name')->varchar(32)->notNull();
    $schema('gender')->int(1);
    $schema->primaryKey('id');
    $schema->primaryKey('xid');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can execute query with multy uniqe', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL CREATE TABLE clauses');

    $this->createConnection($engine);

    $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

    $schema('id')->int(3)->notNull();
    $schema('name')->varchar(32)->notNull();
    $schema('gender')->int(1);
    $schema->unique('id');
    $schema->unique('name');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can generate create database with engine', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ENGINE and CHARACTER SET clauses');

    $this->createConnection($engine);

    $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);

    $schema('id')->int(3)->notNull();
    $schema('name')->varchar(32)->notNull();
    $schema('gender')->int(1);
    $schema->primaryKey('id');
    $schema->engine(Create::INNODB);
    $schema->character('utf8mb4');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can generate default constraint', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL CREATE TABLE clauses');

    $this->createConnection($engine);

    $schema = new Create($this->pdoSchema->getDatabase(), 'profiles', $this->pdoSchema);
    $schema('PersonID')->int()->unsigned()->default(1);
    $schema('LastName')->varchar(255)->default('-');
    $schema('sufix')->varchar(15)->defaultNull();
    $schema->primaryKey('PersonID');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can generate query with comment', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL column COMMENT clause');

    $this->createConnection($engine);

    $schema = new Create($this->pdoSchema->getDatabase(), 'test', $this->pdoSchema);
    $schema('PersonID')->int();
    $schema('LastName')->varchar(255)->comment('The last name of the person associated with this ID');
    $schema->primaryKey('PersonID');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());