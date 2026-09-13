<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\Query\Query;
use Omega\Database\Query\Insert;
use Tests\Database\ManagesDatabase;
use Tests\Database\Support\Profile;

uses(ManagesDatabase::class);

covers(Query::class);
covers(Insert::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can filter model', function (string $engine): void {
    $profiles = [
        'taylor' => [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
            'age'    => 45,
        ],
        'nuno' => [
            'user'   => 'nuno',
            'name'   => 'nuno maduro',
            'gender' => 'male',
            'age'    => 40,
        ],
        'jesica' => [
            'user'   => 'jesica',
            'name'   => 'jesica w',
            'gender' => 'female',
            'age'    => 38,
        ],
        'pradana' => [
            'user'   => 'pradana',
            'name'   => 'sony pradana',
            'gender' => 'male',
            'age'    => 29,
        ],
    ];

    $this->createConnection($engine);
    $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
    (new Insert('profiles', $this->pdo))
        ->rows(array_values($profiles))
        ->execute();

    $profiles = new Profile($this->pdo, []);
    $profiles->filterGender('male');
    $profiles->read();

    foreach ($profiles->get() as $profile) {
        expect($profile->getter('gender'))->toEqual('male');
    }
})->with(ManagesDatabase::engineProvider());

test('it can filter model chain', function (string $engine): void {
    $profiles = [
        'taylor' => [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
            'age'    => 45,
        ],
        'nuno' => [
            'user'   => 'nuno',
            'name'   => 'nuno maduro',
            'gender' => 'male',
            'age'    => 40,
        ],
        'jesica' => [
            'user'   => 'jesica',
            'name'   => 'jesica w',
            'gender' => 'female',
            'age'    => 38,
        ],
        'pradana' => [
            'user'   => 'pradana',
            'name'   => 'sony pradana',
            'gender' => 'male',
            'age'    => 29,
        ],
    ];

    $this->createConnection($engine);
    $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
    (new Insert('profiles', $this->pdo))
        ->rows(array_values($profiles))
        ->execute();

    $profiles = new Profile($this->pdo, []);
    $profiles->filterGender('male');
    $profiles->filterAge(30);
    $profiles->read();

    foreach ($profiles->get() as $profile) {
        expect($profile->getter('gender'))->toEqual('male');
        expect($profile->getter('gender'))->toBeGreaterThan(30);
    }
})->with(ManagesDatabase::engineProvider());

test('it can limit order', function (string $engine): void {
    $profiles = [
        'taylor' => [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
            'age'    => 45,
        ],
        'nuno' => [
            'user'   => 'nuno',
            'name'   => 'nuno maduro',
            'gender' => 'male',
            'age'    => 40,
        ],
        'jesica' => [
            'user'   => 'jesica',
            'name'   => 'jesica w',
            'gender' => 'female',
            'age'    => 38,
        ],
        'pradana' => [
            'user'   => 'pradana',
            'name'   => 'sony pradana',
            'gender' => 'male',
            'age'    => 29,
        ],
    ];

    $this->createConnection($engine);
    $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
    (new Insert('profiles', $this->pdo))
        ->rows(array_values($profiles))
        ->execute();

    $profiles = new Profile($this->pdo, []);
    $profiles->limitEnd(2);
    $profiles->read();

    expect($profiles->get()->count())->toEqual(2);
})->with(ManagesDatabase::engineProvider());

test('it can limit offset', function (string $engine): void {
    $profiles = [
        'taylor' => [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
            'age'    => 45,
        ],
        'nuno' => [
            'user'   => 'nuno',
            'name'   => 'nuno maduro',
            'gender' => 'male',
            'age'    => 40,
        ],
        'jesica' => [
            'user'   => 'jesica',
            'name'   => 'jesica w',
            'gender' => 'female',
            'age'    => 38,
        ],
        'pradana' => [
            'user'   => 'pradana',
            'name'   => 'sony pradana',
            'gender' => 'male',
            'age'    => 29,
        ],
    ];

    $this->createConnection($engine);
    $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
    (new Insert('profiles', $this->pdo))
        ->rows(array_values($profiles))
        ->execute();

    $profiles = new Profile($this->pdo, []);
    $profiles->limitOffset(1, 2);
    $profiles->read();

    expect($profiles->get()->count())->toEqual(1);
})->with(ManagesDatabase::engineProvider());

test('it can short order', function (string $engine): void {
    $profiles = [
        'taylor' => [
            'user'   => 'taylor',
            'name'   => 'taylor otwell',
            'gender' => 'male',
            'age'    => 45,
        ],
        'nuno' => [
            'user'   => 'nuno',
            'name'   => 'nuno maduro',
            'gender' => 'male',
            'age'    => 40,
        ],
        'jesica' => [
            'user'   => 'jesica',
            'name'   => 'jesica w',
            'gender' => 'female',
            'age'    => 38,
        ],
        'pradana' => [
            'user'   => 'pradana',
            'name'   => 'sony pradana',
            'gender' => 'male',
            'age'    => 29,
        ],
    ];

    $this->createConnection($engine);
    $this->pdo->query('CREATE TABLE profiles (
        user      varchar(32)  NOT NULL,
        name      varchar(100) NOT NULL,
        gender    varchar(10) NOT NULL,
        age       int(3) NOT NULL,
        PRIMARY KEY (user)
    )')->execute();
    (new Insert('profiles', $this->pdo))
        ->rows(array_values($profiles))
        ->execute();

    $profiles = new Profile($this->pdo, []);

    $profiles->order('user', Query::ORDER_ASC);
    $profiles->read();
    expect($profiles->first())->toEqual([
        'user'   => 'jesica',
        'name'   => 'jesica w',
        'gender' => 'female',
        'age'    => 38,
    ]);
})->with(ManagesDatabase::engineProvider());