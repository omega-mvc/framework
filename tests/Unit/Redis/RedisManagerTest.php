<?php

declare(strict_types=1);

namespace Tests\Redis;

use Exception;
use Omega\Redis\Redis;
use Omega\Redis\RedisManager;
use RedisException;
use ReflectionProperty;

use function expect;
use function extension_loaded;
use function file_exists;

covers(Redis::class);
covers(RedisManager::class);

beforeEach(function (): void {
    if (!extension_loaded('redis')) {
        $this->markTestSkipped('Redis extension not loaded.');
    }
});

afterEach(function (): void {
    if (extension_loaded('redis')) {
        createRedisDriver()->flushDb();
    }
});

it('can set and get default driver', function (): void {
    $manager = new RedisManager();
    $driver  = createRedisDriver();

    $manager->setDefaultDriver($driver);

    expect($manager->driver()->getName())->toBe('PHPRedis');
    expect($manager->driver())->toBe($driver);

    $manager->set('manager-key', 'manager-value');

    expect($driver->get('manager-key'))->toBe('manager-value');
    expect($manager->get('manager-key'))->toBe('manager-value');
});

it('can set and get named drivers', function (): void {
    $manager = new RedisManager();

    $defaultDriver = createRedisDriver();
    $manager->setDefaultDriver($defaultDriver);
    $manager->set('default-key', 'default-value');

    $namedDriver = new Redis([
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 2,
    ]);
    $manager->setDriver('second', $namedDriver);

    expect($manager->driver('second')->getName())->toBe('PHPRedis');
    expect($manager->driver('second'))->toBe($namedDriver);

    $manager->driver('second')->set('named-key', 'named-value');

    expect($manager->driver('second')->get('named-key'))->toBe('named-value');
    expect($manager->get('default-key'))->toBe('default-value');
    expect($manager->get('named-key'))->toBeFalse();
    expect($manager->driver('second')->get('default-key'))->toBeFalse();
});

it('can use closure as driver', function (): void {
    $manager = new RedisManager();
    $manager->setDriver('lazy', function () {
        return createRedisDriver();
    });

    expect($manager->driver('lazy')->getName())->toBe('PHPRedis');

    $manager->driver('lazy')->set('lazy-key', 'lazy-value');

    expect($manager->driver('lazy')->get('lazy-key'))->toBe('lazy-value');
});

it('can connect via unix socket', function (): void {
    $socketPath = '/var/run/redis/redis.sock';

    if (false === file_exists($socketPath)) {
        $this->markTestSkipped("Redis socket not found at {$socketPath}.");
    }

    try {
        $driver = new Redis([
            'unix_socket' => $socketPath,
            'database'    => 1,
        ]);
    } catch (RedisException $e) {
        $this->markTestSkipped("Redis socket unreachable at {$socketPath}: {$e->getMessage()}");
    }

    $manager = new RedisManager();
    $manager->setDefaultDriver($driver);

    expect($manager->set('socket-key', 'socket-value'))->toBeTrue();
    expect($manager->get('socket-key'))->toBe('socket-value');
});

it('resolves the default connection from config when no default driver is set', function (): void {
    $manager = new RedisManager();
    $manager->setConfig([
        'default'     => 'default',
        'connections' => [
            'default' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 1],
        ],
    ]);

    $driver = $manager->driver();

    expect($driver)->toBeInstanceOf(Redis::class);
    expect($driver->getName())->toBe('PHPRedis');
    expect($manager->driver())->toBe($driver);
});

it('resolves a named connection from config and caches it', function (): void {
    $manager = new RedisManager();
    $manager->setConfig([
        'default'     => 'fallback',
        'connections' => [
            'fallback' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 1],
            'named'    => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 2],
        ],
    ]);

    $driver = $manager->connection('named');

    expect($driver)->toBeInstanceOf(Redis::class);
    expect($manager->connection('named'))->toBe($driver);
});

it('falls back to the default connection for an unknown named driver', function (): void {
    $manager = new RedisManager();
    $manager->setConfig([
        'default'     => 'default',
        'connections' => [
            'default' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 1],
        ],
    ]);

    expect($manager->driver('not-registered'))->toBeInstanceOf(Redis::class);
});

it('throws when a named connection is not configured', function (): void {
    $manager = new RedisManager();
    $manager->setConfig([
        'default'     => 'default',
        'connections' => [
            'default' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 1],
        ],
    ]);

    expect(fn () => $manager->connection('unknown'))
        ->toThrow(Exception::class, 'Can not use connection unknown.');
});

it('throws when no default connection name is configured', function (): void {
    $manager = new RedisManager();

    expect(fn () => $manager->driver())
        ->toThrow(Exception::class, 'No default Redis connection has been configured.');
});

it('throws when a registered driver cannot be resolved', function (): void {
    $manager = new RedisManager();

    $property = new ReflectionProperty(RedisManager::class, 'driver');
    $property->setValue($manager, ['broken' => 'not-a-driver']);

    expect(fn () => $manager->driver('broken'))
        ->toThrow(Exception::class, 'Can not use driver broken.');
});

it('resolves a named connection registered as a driver', function (): void {
    $manager = new RedisManager();

    $manager->setDriver('connection-a', fn (): Redis => createRedisDriver());

    expect($manager->connection('connection-a'))->toBeInstanceOf(Redis::class);
});

it('delegates redis operations to the default driver', function (): void {
    $manager = new RedisManager();
    $manager->setDefaultDriver(createRedisDriver());

    expect($manager->set('delegated-key', 'delegated-value'))->toBeTrue();
    expect($manager->get('delegated-key'))->toBe('delegated-value');

    expect($manager->del('delegated-key'))->toBe(1);
    expect($manager->exists('delegated-key'))->toBeFalse();

    expect($manager->incr('delegated-counter'))->toBe(1);
    expect($manager->decr('delegated-counter'))->toBe(0);

    $manager->set('delegated-pattern-1', 'a');
    $manager->set('delegated-pattern-2', 'b');
    expect($manager->keys('delegated-pattern-*'))->toHaveCount(2);

    expect($manager->getName())->toBe('PHPRedis');
    expect($manager->command('ping'))->toBeTrue();

    $manager->__call('hSet', ['delegated-hash', 'field', 'value']);
    expect($manager->__call('hGet', ['delegated-hash', 'field']))->toBe('value');

    $manager->disconnect();
});

function createRedisDriver(): Redis
{
    return new Redis([
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 1,
    ]);
}
