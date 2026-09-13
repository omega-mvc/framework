<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Tests\Database\ManagesDatabase;
use Tests\Database\Support\User;

uses(ManagesDatabase::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can read data', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $password = password_hash('password', PASSWORD_DEFAULT);
    $this->createUser([
        [
            'user'     => 'nuno',
            'password' => $password,
            'stat'     => 90,
        ],
        [
            'user'     => 'taylor',
            'password' => $password,
            'stat'     => 100,
        ],
        [
            'user'     => 'pradana',
            'password' => $password,
            'stat'     => 80,
        ],
    ]);

    $users = new User($this->pdo, []);
    $users->read();

    foreach ($users->get() as $user) {
        expect($user->read())->toBeTrue();
    }
})->with(ManagesDatabase::engineProvider());

test('it can update data', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $password = password_hash('password', PASSWORD_DEFAULT);
    $this->createUser([
        [
            'user'     => 'nuno',
            'password' => $password,
            'stat'     => 90,
        ],
        [
            'user'     => 'taylor',
            'password' => $password,
            'stat'     => 100,
        ],
        [
            'user'     => 'pradana',
            'password' => $password,
            'stat'     => 80,
        ],
    ]);

    $users = new User($this->pdo, []);
    $users->read();

    foreach ($users->get() as $user) {
        $user->setter('stat', 0);
        expect($user->update())->toBeTrue();
    }
})->with(ManagesDatabase::engineProvider());

test('it can delete data', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $password = password_hash('password', PASSWORD_DEFAULT);
    $this->createUser([
        [
            'user'     => 'nuno',
            'password' => $password,
            'stat'     => 90,
        ],
        [
            'user'     => 'taylor',
            'password' => $password,
            'stat'     => 100,
        ],
        [
            'user'     => 'pradana',
            'password' => $password,
            'stat'     => 80,
        ],
    ]);

    $users = new User($this->pdo, []);
    $users->read();

    foreach ($users->get() as $user) {
        expect($user->delete())->toBeTrue();
    }
})->with(ManagesDatabase::engineProvider());

test('it can update all with single query', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $password = password_hash('password', PASSWORD_DEFAULT);
    $this->createUser([
        [
            'user'     => 'nuno',
            'password' => $password,
            'stat'     => 90,
        ],
        [
            'user'     => 'taylor',
            'password' => $password,
            'stat'     => 100,
        ],
        [
            'user'     => 'pradana',
            'password' => $password,
            'stat'     => 80,
        ],
    ]);

    $users  = new User($this->pdo, []);
    $users->read();

    $update = $users->get()->update([
        'stat' => 0,
    ]);

    expect($update)->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can delete all with single query', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $password = password_hash('password', PASSWORD_DEFAULT);
    $this->createUser([
        [
            'user'     => 'nuno',
            'password' => $password,
            'stat'     => 90,
        ],
        [
            'user'     => 'taylor',
            'password' => $password,
            'stat'     => 100,
        ],
        [
            'user'     => 'pradana',
            'password' => $password,
            'stat'     => 80,
        ],
    ]);

    $users  = new User($this->pdo, []);
    $users->read();

    $delete = $users->get()->delete();

    expect($delete)->toBeTrue();
})->with(ManagesDatabase::engineProvider());