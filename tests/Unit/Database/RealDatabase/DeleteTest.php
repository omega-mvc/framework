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

test('it can delete', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->delete()
        ->execute()
    ;

    $this->assertUserNotExist('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can delete with between', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->delete()
        ->between('stat', 0, 100)
        ->execute()
    ;

    $this->assertUserNotExist('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can delete with compare', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->delete()
        ->compare('user', '=', 'taylor')
        ->execute()
    ;

    $this->assertUserNotExist('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can delete with equal', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->delete()
        ->equal('user', 'taylor')
        ->execute()
    ;

    $this->assertUserNotExist('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can delete with in', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->delete()
        ->in('user', ['taylor'])
        ->execute()
    ;

    $this->assertUserNotExist('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can delete with like', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->delete()
        ->like('user', 'tay%')
        ->execute()
    ;

    $this->assertUserNotExist('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can delete with where', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->delete()
        ->where('user = :user', [
            [':user', 'taylor'],
        ])
        ->execute()
    ;

    $this->assertUserNotExist('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can delete with multy condition', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    Query::from('users', $this->pdo)
        ->delete()
        ->compare('stat', '>', 1)
        ->where('user = :user', [
            [':user', 'taylor'],
        ])
        ->execute()
    ;

    $this->assertUserNotExist('taylor');
})->with(ManagesDatabase::engineProvider());