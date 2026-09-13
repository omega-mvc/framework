<?php

declare(strict_types=1);

namespace Tests\Database\RealDatabase;

use Omega\Database\Query\Query;
use Omega\Database\Query\Join\InnerJoin;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;

uses(ManagesDatabase::class);
uses(UserTrait::class);

covers(Query::class);
covers(InnerJoin::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can select query', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->all() ?: []
    ;

    
    expect($users[0])->toHaveKey('user');
    expect($users[0])->toHaveKey('password');
    expect($users[0])->toHaveKey('stat');
})->with(ManagesDatabase::engineProvider());

test('it can select query onlyuser', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select(['user'])
        ->all() ?: []
    ;

    
    expect($users[0])->toHaveKey('user');
    expect($users[0])->not->toHaveKey('password');
    expect($users[0])->not->toHaveKey('stat');
})->with(ManagesDatabase::engineProvider());

test('it can select query with between', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->between('stat', 0, 100)
        ->all() ?: []
    ;

    
    expect($users[0]['user'])->toEqual('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can select query with compare', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->compare('user', '=', 'taylor')
        ->all() ?: []
    ;

    
    expect($users[0]['user'])->toEqual('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can select query with equal', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->equal('user', 'taylor')
        ->all() ?: []
    ;

    
    expect($users[0]['user'])->toEqual('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can select query with in', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->in('user', ['taylor'])
        ->all() ?: []
    ;

    
    expect($users[0]['user'])->toEqual('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can select query with like', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->like('user', 'tay%')
        ->all() ?: []
    ;

    
    expect($users[0]['user'])->toEqual('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can select query with where', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->where('user = :user', [
            [':user', 'taylor'],
        ])
        ->all() ?: []
    ;

    
    expect($users[0]['user'])->toEqual('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can select query with multy condition', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->compare('stat', '>', 1)
        ->where('user = :user', [
            [':user', 'taylor'],
        ])
        ->all() ?: []
    ;

    
    expect($users[0]['user'])->toEqual('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can select query with limit', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->limit(0, 1)
        ->all() ?: []
    ;

    
    expect($users[0])->toHaveKey('user');
    expect($users[0])->toHaveKey('password');
    expect($users[0])->toHaveKey('stat');
})->with(ManagesDatabase::engineProvider());

test('it can select query with offset', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->limitStart(0)
        ->offset(1)
        ->all() ?: []
    ;

    
    expect($users[0])->toHaveKey('user');
    expect($users[0])->toHaveKey('password');
    expect($users[0])->toHaveKey('stat');
})->with(ManagesDatabase::engineProvider());

test('it can select query with limit offset', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->limitOffset(0, 10)
        ->all() ?: []
    ;

    
    expect($users[0])->toHaveKey('user');
    expect($users[0])->toHaveKey('password');
    expect($users[0])->toHaveKey('stat');
})->with(ManagesDatabase::engineProvider());

test('it can select query with strit mode', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $users = Query::from('users', $this->pdo)
        ->select()
        ->equal('user', 'taylor')
        ->equal('stat', 99)
        ->strictMode(false)
        ->all() ?: []
    ;

    
    expect($users[0]['user'])->toEqual('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can select join', function (string $engine): void {
    $this->createConnection($engine);
    $this->seedDefaultUser();

    $this->pdo
        ->query('CREATE TABLE profiles (
            user varchar(32) NOT NULL,
            real_name varchar(500) NOT NULL,
            PRIMARY KEY (user)
          )')
        ->execute()
    ;

    $this->pdo
        ->query('INSERT INTO profiles (
            user,
            real_name
          ) VALUES (
            :user,
            :real_name
          )')
        ->bind(':user', 'taylor')
        ->bind(':real_name', 'taylor otwell')
        ->execute()
    ;

    $users = Query::from('users', $this->pdo)
        ->select()
        ->equal('user', 'taylor')
        ->join(InnerJoin::ref('profiles', 'user '))
        ->all() ?: []
    ;

    
    expect($users[0])->toHaveKey('user');
    expect($users[0])->toHaveKey('password');
    expect($users[0])->toHaveKey('stat');
    expect($users[0])->toHaveKey('real_name');
})->with(ManagesDatabase::engineProvider());
