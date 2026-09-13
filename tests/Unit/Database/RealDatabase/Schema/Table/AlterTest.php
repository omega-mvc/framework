<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase\Schema\Table;

use Omega\Database\Schema\Table\Alter;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

covers(Alter::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can excute query using modify column', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo
        ->query('CREATE TABLE profiles (
            user varchar(10) NOT NULL,
            name varchar(500) NOT NULL,
            stat int(2) NOT NULL,
            create_at int(12) NOT NULL,
            update_at int(12) NOT NULL,
            PRIMARY KEY (user)
          )')
        ->execute()
    ;

    $schema = new Alter(
        $this->pdoSchema->getDatabase(),
        'profiles',
        $this->pdoSchema
    );
    $schema->column('user')->varchar(15);

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can excute query using add column', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo
        ->query('CREATE TABLE profiles (
            user varchar(10) NOT NULL,
            name varchar(500) NOT NULL,
            stat int(2) NOT NULL,
            create_at int(12) NOT NULL,
            update_at int(12) NOT NULL,
            PRIMARY KEY (user)
          )')
        ->execute()
    ;

    $schema = new Alter(
        $this->pdoSchema->getDatabase(),
        'profiles',
        $this->pdoSchema
    );
    $schema->add('PersonID')->int();
    $schema->add('LastName')->varchar(255);

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can excute query using drop column', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo
        ->query('CREATE TABLE profiles (
            user varchar(10) NOT NULL,
            name varchar(500) NOT NULL,
            stat int(2) NOT NULL,
            create_at int(12) NOT NULL,
            update_at int(12) NOT NULL,
            PRIMARY KEY (user)
          )')
        ->execute()
    ;

    $schema = new Alter(
        $this->pdoSchema->getDatabase(),
        'profiles',
        $this->pdoSchema
    );
    $schema->drop('create_at');
    $schema->drop('update_at');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can excute query using rename column', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo
        ->query('CREATE TABLE profiles (
            user varchar(10) NOT NULL,
            name varchar(500) NOT NULL,
            stat int(2) NOT NULL,
            create_at int(12) NOT NULL,
            update_at int(12) NOT NULL,
            PRIMARY KEY (user)
          )')
        ->execute()
    ;

    $schema = new Alter(
        $this->pdoSchema->getDatabase(),
        'profiles',
        $this->pdoSchema
    );
    $schema->rename('stat', 'take');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can excute query using renames column', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo
        ->query('CREATE TABLE profiles (
            user varchar(10) NOT NULL,
            name varchar(500) NOT NULL,
            stat int(2) NOT NULL,
            create_at int(12) NOT NULL,
            update_at int(12) NOT NULL,
            PRIMARY KEY (user)
          )')
        ->execute()
    ;

    $schema = new Alter(
        $this->pdoSchema->getDatabase(),
        'profiles',
        $this->pdoSchema
    );
    $schema->rename('stat', 'take');
    $schema->rename('update_at', 'modify_at');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can excute query using alter column', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo
        ->query('CREATE TABLE profiles (
            user varchar(10) NOT NULL,
            name varchar(500) NOT NULL,
            stat int(2) NOT NULL,
            create_at int(12) NOT NULL,
            update_at int(12) NOT NULL,
            PRIMARY KEY (user)
          )')
        ->execute()
    ;

    $schema = new Alter(
        $this->pdoSchema->getDatabase(),
        'profiles',
        $this->pdoSchema
    );
    $schema->column('user')->varchar(15);
    $schema->add('PersonID')->int();
    $schema->drop('create_at');
    $schema->rename('stat', 'take');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can excute query using modify add with order', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo
        ->query('CREATE TABLE profiles (
            user varchar(10) NOT NULL,
            name varchar(500) NOT NULL,
            stat int(2) NOT NULL,
            create_at int(12) NOT NULL,
            update_at int(12) NOT NULL,
            PRIMARY KEY (user)
          )')
        ->execute()
    ;

    $schema = new Alter(
        $this->pdoSchema->getDatabase(),
        'profiles',
        $this->pdoSchema
    );
    $schema->add('uuid')->varchar(15)->first();
    $schema->add('last_name')->varchar(32)->after('name');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can excute query using modify column with order', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'MySQL ALTER TABLE syntax');

    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo
        ->query('CREATE TABLE profiles (
            user varchar(10) NOT NULL,
            name varchar(500) NOT NULL,
            stat int(2) NOT NULL,
            create_at int(12) NOT NULL,
            update_at int(12) NOT NULL,
            PRIMARY KEY (user)
          )')
        ->execute()
    ;

    $schema = new Alter(
        $this->pdoSchema->getDatabase(),
        'profiles',
        $this->pdoSchema
    );
    $schema('create_at')->varchar(15)->after('user');
    $schema->column('update_at')->varchar(15)->after('user');

    expect($schema->execute())->toBeTrue();
})->with(ManagesDatabase::engineProvider());