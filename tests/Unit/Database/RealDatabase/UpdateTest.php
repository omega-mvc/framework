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

test('it can update', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->update()
        ->value('stat', 0)
        ->execute()
    ;

    $this->assertUserStat('taylor', 0);
})->with(ManagesDatabase::engineProvider());

test('it can update with between', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->update()
        ->value('stat', 0)
        ->between('stat', 0, 100)
        ->execute()
    ;

    $this->assertUserStat('taylor', 0);
})->with(ManagesDatabase::engineProvider());

test('it can update with compare', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->update()
        ->value('stat', 0)
        ->compare('user', '=', 'taylor')
        ->execute()
    ;

    $this->assertUserStat('taylor', 0);
})->with(ManagesDatabase::engineProvider());

test('it can update with equal', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->update()
        ->value('stat', 0)
        ->equal('user', 'taylor')
        ->execute()
    ;

    $this->assertUserStat('taylor', 0);
})->with(ManagesDatabase::engineProvider());

test('it can update with in', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->update()
        ->value('stat', 0)
        ->in('user', ['taylor'])
        ->execute()
    ;

    $this->assertUserStat('taylor', 0);
})->with(ManagesDatabase::engineProvider());

test('it can update with like', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->update()
        ->value('stat', 0)
        ->like('user', 'tay%')
        ->execute()
    ;

    $this->assertUserStat('taylor', 0);
})->with(ManagesDatabase::engineProvider());

test('it can update with where', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->update()
        ->value('stat', 0)
        ->where('user = :user', [
            [':user', 'taylor'],
        ])
        ->execute()
    ;

    $this->assertUserStat('taylor', 0);
})->with(ManagesDatabase::engineProvider());

test('it can update with multy condition', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->update()
        ->value('stat', 0)
        ->compare('stat', '>', 1)
        ->where('user = :user', [
            [':user', 'taylor'],
        ])
        ->execute()
    ;

    $this->assertUserStat('taylor', 0);
})->with(ManagesDatabase::engineProvider());