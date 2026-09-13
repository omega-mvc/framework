<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\Query\Insert;
use Tests\Database\ManagesDatabase;
use Tests\Database\Support\Order;
use Tests\Database\Support\Profile;
use Tests\Database\Support\User;

uses(ManagesDatabase::class);

covers(Insert::class);

afterEach(function (): void {
    $this->dropConnection();
});

test('it can create data', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user = new User($this->pdo, [
        [
            'user'     => 'nuno',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 50,
        ],
    ]);

    expect($user->insert())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can read data', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user = new User($this->pdo, []);

    expect($user->read())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can update data', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();

    $user->setter('stat', 75);

    expect($user->update())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can delete data', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();
    expect($user->delete())->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can get first', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $users = new User($this->pdo, []);
    $users->identifier()->equal('user', 'taylor');
    $users->read();

    expect($users->first())->toEqual([
        'user' => 'taylor',
        'stat' => 100,
    ]);
})->with(ManagesDatabase::engineProvider());

test('it can get has one', function (string $engine): void {
    // profile
    $profile = [
        'user'   => 'taylor',
        'name'   => 'taylor otwell',
        'gender' => 'male',
    ];
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $this->pdo
        ->query('CREATE TABLE profiles (
            user      varchar(32)  NOT NULL,
            name      varchar(100) NOT NULL,
            gender    varchar(10) NOT NULL,
            PRIMARY KEY (user)
        )')
        ->execute()
    ;

    new Insert('profiles', $this->pdo)
        ->rows([$profile])
        ->execute();

    $user   = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();
    $result = $user->hasOne(Profile::class, 'user');
    expect($result->first())->toEqual($profile);
})->with(ManagesDatabase::engineProvider());

test('it can get has one using magic getter', function (string $engine): void {
    // profile
    $profile = [
        'user'   => 'taylor',
        'name'   => 'taylor otwell',
        'gender' => 'male',
    ];
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $this->pdo
        ->query('CREATE TABLE profiles (
            user      varchar(32)  NOT NULL,
            name      varchar(100) NOT NULL,
            gender    varchar(10) NOT NULL,
            PRIMARY KEY (user)
        )')
        ->execute()
    ;

    new Insert('profiles', $this->pdo)
        ->rows([$profile])
        ->execute();

    $user   = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();
    expect($user->profile)->toEqual($profile);
})->with(ManagesDatabase::engineProvider());

test('it can get has one with table name', function (string $engine): void {
    // profile
    $profile = [
        'user'   => 'taylor',
        'name'   => 'taylor otwell',
        'gender' => 'male',
    ];
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $this->pdo
        ->query('CREATE TABLE profiles (
            user      varchar(32)  NOT NULL,
            name      varchar(100) NOT NULL,
            gender    varchar(10) NOT NULL,
            PRIMARY KEY (user)
        )')
        ->execute()
    ;

    new Insert('profiles', $this->pdo)
        ->rows([$profile])
        ->execute();

    $user   = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();
    $result = $user->hasOne('profiles', 'user');
    expect($result->first())->toEqual($profile);
})->with(ManagesDatabase::engineProvider());

test('it can get has many', function (string $engine): void {
    // order
    $order = [
        [
            'id'     => '1',
            'user'   => 'taylor',
            'name'   => 'order 1',
            'type'   => 'gadget',
        ], [
            'id'     => '3',
            'user'   => 'taylor',
            'name'   => 'order 2',
            'type'   => 'gadget',
        ],
    ];
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $this->pdo
        ->query('CREATE TABLE orders (
            id   varchar(3)  NOT NULL,
            user varchar(32)  NOT NULL,
            name varchar(100) NOT NULL,
            type varchar(30) NOT NULL,
            PRIMARY KEY (id)
        )')
        ->execute()
    ;

    new Insert('orders', $this->pdo)
        ->rows($order)
        ->execute();

    $user   = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();
    $result = $user->hasMany(Order::class, 'user');
    expect($result->toArrayArray())->toEqual($order);
})->with(ManagesDatabase::engineProvider());

test('it can get has many with magic getter', function (string $engine): void {
    // order
    $order = [
        [
            'id'     => '1',
            'user'   => 'taylor',
            'name'   => 'order 1',
            'type'   => 'gadget',
        ], [
            'id'     => '3',
            'user'   => 'taylor',
            'name'   => 'order 2',
            'type'   => 'gadget',
        ],
    ];
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $this->pdo
        ->query('CREATE TABLE orders (
            id   varchar(3)  NOT NULL,
            user varchar(32)  NOT NULL,
            name varchar(100) NOT NULL,
            type varchar(30) NOT NULL,
            PRIMARY KEY (id)
        )')
        ->execute()
    ;

    new Insert('orders', $this->pdo)
        ->rows($order)
        ->execute();

    $user   = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();
    expect($user->orders)->toEqual($order);
})->with(ManagesDatabase::engineProvider());

test('it can get has many with table name', function (string $engine): void {
    // order
    $order = [
        [
            'id'     => '1',
            'user'   => 'taylor',
            'name'   => 'order 1',
            'type'   => 'gadget',
        ], [
            'id'     => '3',
            'user'   => 'taylor',
            'name'   => 'order 2',
            'type'   => 'gadget',
        ],
    ];
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $this->pdo
        ->query('CREATE TABLE orders (
            id   varchar(3)  NOT NULL,
            user varchar(32)  NOT NULL,
            name varchar(100) NOT NULL,
            type varchar(30) NOT NULL,
            PRIMARY KEY (id)
        )')
        ->execute()
    ;

    new Insert('orders', $this->pdo)
        ->rows($order)
        ->execute();

    $user   = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();
    $result = $user->hasMany(Order::class, 'user');
    expect($result->toArrayArray())->toEqual($order);
})->with(ManagesDatabase::engineProvider());

test('it can get collection', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user   = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();

    $columns = $user->toArray();
    $models  = $user->get()->toArray();

    // tranform to column
    $arr = [];
    foreach ($models as $new) {
        $arr[] = $new->toArray()[0];
    }
    expect($arr)->toEqual($columns);
})->with(ManagesDatabase::engineProvider());

test('it can find using id', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user = User::find('taylor', $this->pdo);

    expect($user->has('user'))->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can find using where', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user = User::where('user = :user', [
        'user' => 'taylor',
    ], $this->pdo);

    expect($user->has('user'))->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can find using equal', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user = User::equal('user', 'taylor', $this->pdo);

    expect($user->has('user'))->toBeTrue();
})->with(ManagesDatabase::engineProvider());

test('it can find all', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user   = new User($this->pdo, []);
    $user->identifier()->equal('user', 'taylor');
    $user->read();
    $columns = $user->toArray();
    $models  = User::all($this->pdo)->toArray();

    // tranform to column
    $arr = [];
    foreach ($models as $new) {
        $arr[] = $new->toArray()[0];
    }
    expect($arr)->toEqual($columns);
})->with(ManagesDatabase::engineProvider());

test('it can find or create', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user = User::findOrCreate('taylor', [
        'user'     => 'taylor',
        'password' => 'password',
        'stat'     => 100,
    ], $this->pdo);

    expect($user->isExist())->toBeTrue();
    expect($user->getter('user', 'nuno'))->toEqual('taylor');
})->with(ManagesDatabase::engineProvider());

test('it can find or create but not exits', function (string $engine): void {
    $this->createConnection($engine);
    $this->createUserSchema();
    $this->createUser([
        [
            'user'     => 'taylor',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'stat'     => 100,
        ],
    ]);

    $user = User::findOrCreate('pradana', [
        'user'     => 'pradana',
        'password' => 'password',
        'stat'     => 100,
    ], $this->pdo);

    expect($user->isExist())->toBeTrue();
    expect($user->getter('user', 'nuno'))->toEqual('pradana');
})->with(ManagesDatabase::engineProvider());