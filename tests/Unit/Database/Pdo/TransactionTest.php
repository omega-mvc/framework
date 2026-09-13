<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Omega\Database\ConnectionInterface;
use PDOException;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can rollback transaction', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
       ->bind(':user', 'test_user')
       ->bind(':password', 'test_password')
       ->bind(':stat', 1)
       ->execute();
    $this->pdo->beginTransaction();
    $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
       ->bind(':stat', 0)
       ->bind(':user', 'test_user')
       ->execute();
    $this->pdo->cancelTransaction();
    $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
       ->bind(':user', 'test_user')
       ->resultset() ?: [];
    
    expect($user[0]['stat'])->toEqual(1);
})->with(ManagesDatabase::engineProvider());

test('it can commit transaction', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
       ->bind(':user', 'test_user')
       ->bind(':password', 'test_password')
       ->bind(':stat', 1)
       ->execute();
    $this->pdo->beginTransaction();
    $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
       ->bind(':stat', 0)
       ->bind(':user', 'test_user')
       ->execute();
    $this->pdo->endTransaction();
    $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
       ->bind(':user', 'test_user')
       ->resultset() ?: [];
    
    expect($user[0]['stat'])->toEqual(0);
})->with(ManagesDatabase::engineProvider());

test('it can commit transaction using closure', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
       ->bind(':user', 'test_user')
       ->bind(':password', 'test_password')
       ->bind(':stat', 1)
       ->execute();

    $test = function (): bool {
        $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
           ->bind(':stat', 0)
           ->bind(':user', 'test_user')
           ->execute();

        return true;
    };

    $transaction = $this->pdo->transaction($test);

    $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
       ->bind(':user', 'test_user')
       ->resultset() ?: [];
    
    expect($user[0]['stat'])->toEqual(0);
    expect($transaction)->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can commit transaction using closure parameter', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
       ->bind(':user', 'test_user')
       ->bind(':password', 'test_password')
       ->bind(':stat', 1)
       ->execute();

    $test = function (ConnectionInterface $pdo): bool {
        $pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
           ->bind(':stat', 0)
           ->bind(':user', 'test_user')
           ->execute();

        return true;
    };

    $transaction = $this->pdo->transaction($test);

    $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
       ->bind(':user', 'test_user')
       ->resultset() ?: [];
    
    expect($user[0]['stat'])->toEqual(0);
    expect($transaction)->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can rollback transaction using closer', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
       ->bind(':user', 'test_user')
       ->bind(':password', 'test_password')
       ->bind(':stat', 1)
       ->execute();

    $test = function (): bool {
        $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
           ->bind(':stat', 0)
           ->bind(':user', 'test_user')
           ->execute();
        $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
           ->bind(':stat', 2)
           ->bind(':user', 'test_user')
           ->execute();

        return false;
    };

    $transaction = $this->pdo->transaction($test);

    $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
       ->bind(':user', 'test_user')
       ->resultset() ?: [];
    
    expect($user[0]['stat'])->toEqual(1);
    expect($transaction)->toBeFalse();
})->with(ManagesDatabase::engineProvider());

test('it can rollback transaction using closer with throw', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();

    $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
       ->bind(':user', 'test_user')
       ->bind(':password', 'test_password')
       ->bind(':stat', 1)
       ->execute();

    $test = function (): bool {
        $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
           ->bind(':stat', 0)
           ->bind(':user', 'test_user')
           ->execute();

        throw new PDOException('Test Exception');
    };

    $transaction =  $this->pdo->transaction($test);

    $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
       ->bind(':user', 'test_user')
       ->resultset() ?: [];
    
    expect($user[0]['stat'])->toEqual(1);
    expect($transaction)->toBeFalse();
})->with(ManagesDatabase::engineProvider());