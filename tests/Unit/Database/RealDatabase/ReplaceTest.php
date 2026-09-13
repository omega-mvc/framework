<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Query;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);
uses(UserTrait::class);

covers(Query::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can replace on new data', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb', 'sqlite'], 'REPLACE INTO statement');

    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->replace()
        ->values([
            'user'      => 'sony',
            'password'  => 'secret',
            'stat'      => 99,
        ])
        ->execute();

    $this->assertUserExist('sony');
})->with(ManagesDatabase::engineProvider());

test('it can replace on exist data', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb', 'sqlite'], 'REPLACE INTO statement');

    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->insert()
        ->values([
            'user'      => 'sony',
            'password'  => 'secret',
            'stat'      => 99,
        ])
        ->execute();

    Query::from('users', $this->pdo)
        ->replace()
        ->values([
            'user'      => 'sony',
            'password'  => 'secret',
            'stat'      => 66,
        ])
        ->execute();

    $this->assertUserStat('sony', 66);
})->with(ManagesDatabase::engineProvider());

test('it can update insertusing one query', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb', 'sqlite'], 'REPLACE INTO statement');

    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->insert()
        ->values([
            'user'      => 'sony',
            'password'  => 'secret',
            'stat'      => 99,
        ])
        ->execute();

    Query::from('users', $this->pdo)
        ->replace()
        ->rows([
            [
                'user'      => 'sony',
                'password'  => 'secret',
                'stat'      => 66,
            ],
            [
                'user'      => 'sony2',
                'password'  => 'secret',
                'stat'      => 66,
            ],
        ])
        ->execute();

    $this->assertUserStat('sony', 66);
    $this->assertUserExist('sony2');
})->with(ManagesDatabase::engineProvider());