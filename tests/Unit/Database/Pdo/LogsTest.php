<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);
uses(UserTrait::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can get log excution connention', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();
    $this->pdo->flushLogs();
    $this->pdo->query('select * from users where user = :user')->bind('user', 'taylor')->resultset();
    $this->pdo->query('select * from users where user = :user')->bind('user', 'taylor')->single();
    $this->pdo->query('delete from users where user = :user')->bind('user', 'taylor')->execute();

    $logs = [
        'select * from users where user = :user',
        'select * from users where user = :user',
        'delete from users where user = :user',
    ];

    // after calculate
    foreach ($this->pdo->getLogs() as $key => $log) {
        expect($logs[$key])->toEqual($log['query']);
        expect($log['duration'])->not->toBeNull();
    }
})->with(ManagesDatabase::engineProvider());

test('it can select query', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    expect($this->pdo->getLogs())->not->toBeEmpty();
    foreach ($this->pdo->getLogs() as $key => $log) {
        expect($log)->toHaveKey('query');
        expect($log)->toHaveKey('started');
        expect($log)->toHaveKey('ended');
        expect($log)->toHaveKey('duration');
    }
})->with(ManagesDatabase::engineProvider());

test('it can flush', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    expect($this->pdo->getLogs())->not->toBeEmpty();
    $this->pdo->flushLogs();
    expect($this->pdo->getLogs())->toBeEmpty();
})->with(ManagesDatabase::engineProvider());

test('it can empty logs get logs', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();
    $this->pdo->flushLogs();

    expect($this->pdo->getLogs())->toBeEmpty(); // Should not throw error
})->with(ManagesDatabase::engineProvider());

test('it can get multiple get logs calls', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();
    $this->pdo->flushLogs();
    $this->pdo->query('SELECT 1')->execute();

    $firstCall  = $this->pdo->getLogs();
    $secondCall = $this->pdo->getLogs();
    $thirdCall  = $this->pdo->getLogs();

    expect($secondCall)->toEqual($firstCall);
    expect($thirdCall)->toEqual($secondCall);
})->with(ManagesDatabase::engineProvider());