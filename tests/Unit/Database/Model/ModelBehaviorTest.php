<?php

declare(strict_types=1);

namespace Tests\Database\Model;

use Omega\Database\ConnectionInterface;
use Omega\Database\Model\Model;
use Omega\Database\Model\ModelCollection;
use Tests\Database\Support\User;

covers(Model::class);
covers(ModelCollection::class);

beforeEach(function (): void {
    $this->connection = $this->createStub(ConnectionInterface::class);
});

/**
 * Build a single-row User model without touching any database engine.
 *
 * @param array<int, array<string, bool|int|string|null>> $rows
 * @return User
 */
function user(ConnectionInterface $connection, array $rows): User
{
    return new User($connection, $rows);
}

/**
 * @return array<int, array<string, bool|int|string|null>>
 */
function singleUser(): array
{
    return [
        [
            'user'     => 'taylor',
            'password' => 'secret',
            'stat'     => 100,
        ],
    ];
}

/**
 * @return array<int, array<string, bool|int|string|null>>
 */
function multiUser(): array
{
    return [
        [
            'user'     => 'nuno',
            'password' => 'secret',
            'stat'     => 90,
        ],
        [
            'user'     => 'taylor',
            'password' => 'secret',
            'stat'     => 100,
        ],
        [
            'user'     => 'pradana',
            'password' => 'secret',
            'stat'     => 80,
        ],
    ];
}

test('it can checkis clean', function (): void {
    $user = user($this->connection, singleUser());

    expect($user->isClean())->toBeTrue();
    expect($user->isClean('stat'))->toBeTrue();
});

test('it can checkis dirty', function (): void {
    $user = user($this->connection, singleUser());
    $user->setter('stat', 75);

    expect($user->isDirty())->toBeTrue();
    expect($user->isDirty('stat'))->toBeTrue();
});

test('it can get change column', function (): void {
    $user = user($this->connection, singleUser());
    expect($user->changes())->toEqual([]);

    $user->setter('stat', 75);
    expect($user->changes())->toEqual([
        'stat' => 75,
    ]);
});

test('it can hidde column', function (): void {
    $user = user($this->connection, singleUser());

    expect($user->first())->not->toHaveKey('password');
});

test('it can get primary key', function (): void {
    $user = user($this->connection, singleUser());

    expect($user->getPrimaryKey())->toEqual('taylor');
});

test('it can convert to array', function (): void {
    $user = user($this->connection, singleUser());

    expect($user->toArray())->toEqual([
        [
            'user' => 'taylor',
            'stat' => 100,
        ],
    ]);
});

test('it can get using getter in column', function (): void {
    $user = user($this->connection, singleUser());
    expect($user->getter('stat', 0))->toEqual(100);
});

test('it can set using setterter in column', function (): void {
    $user = user($this->connection, singleUser());
    $user->setter('stat', 80);
    $columns = $user->toArray();
    expect($columns[0]['stat'])->toEqual(80);
});

test('it can check exist', function (): void {
    $user = user($this->connection, singleUser());

    expect($user->has('user'))->toBeTrue();
});

test('it can get using magic getter in column', function (): void {
    $user = user($this->connection, singleUser());
    expect($user->stat)->toEqual(100);
});

test('it can set using magic setterter in column', function (): void {
    $user = user($this->connection, singleUser());
    $user->stat = 80;
    $columns = $user->toArray();
    expect($columns[0]['stat'])->toEqual(80);
});

test('it can get using array', function (): void {
    $user = user($this->connection, singleUser());
    expect($user['stat'])->toEqual(100);
});

test('it can set using array', function (): void {
    $user = user($this->connection, singleUser());
    $user['stat'] = 80;
    $columns = $user->toArray();
    expect($columns[0]['stat'])->toEqual(80);
});

test('it can check using magic isset', function (): void {
    $user = user($this->connection, singleUser());
    expect(isset($user['user']))->toBeTrue();
});

test('it can unset using array', function (): void {
    $user = user($this->connection, singleUser());

    unset($user['stat']);
    $columns = $user->toArray();
    expect($columns[0]['stat'])->toEqual(100);
});

test('it can convert collection to model every items', function (): void {
    $user = user($this->connection, multiUser());

    foreach ($user->get() as $model) {
        expect($model->has('user'))->toBeTrue();
    }
});

test('it can get all primary keys from collection', function (): void {
    $user = user($this->connection, multiUser());

    expect($user->get()->getPrimaryKey())->toEqualCanonicalizing(['nuno', 'taylor', 'pradana']);
});

test('it can check collection is clean', function (): void {
    $user = user($this->connection, multiUser());

    expect($user->get()->isClean())->toBeTrue();
});

test('it can check collection is dirty', function (): void {
    $user = user($this->connection, multiUser());

    expect($user->get()->isDirty())->toBeFalse();
});