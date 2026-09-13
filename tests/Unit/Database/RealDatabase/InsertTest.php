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

test('it can insert data', function (string $engine): void {
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

    $this->assertUserExist('sony');
})->with(ManagesDatabase::engineProvider());

test('it can insert multy raw', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->insert()
        ->rows([
            [
                'user'      => 'sony',
                'password'  => 'secret',
                'stat'      => 1,
            ], [
                'user'      => 'pradana',
                'password'  => 'secret',
                'stat'      => 2,
            ],
        ])
        ->execute();

    $this->assertUserExist('sony');
    $this->assertUserExist('pradana');
})->with(ManagesDatabase::engineProvider());

test('it can replace on exist data', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'INSERT ON DUPLICATE KEY UPDATE');

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
        ->insert()
        ->values([
            'user'      => 'sony',
            'password'  => 'secret',
            'stat'      => 66,
        ])
        ->on('stat')
        ->execute();

    $this->assertUserStat('sony', 66);
})->with(ManagesDatabase::engineProvider());

test('it can update insertusing one query', function (string $engine): void {
    $this->requiresOneOf($engine, ['mysql', 'mariadb'], 'INSERT ON DUPLICATE KEY UPDATE');

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
        ->insert()
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
        ->on('user')
        ->on('stat')
        ->execute();

    $this->assertUserStat('sony', 66);
    $this->assertUserExist('sony2');
})->with(ManagesDatabase::engineProvider());