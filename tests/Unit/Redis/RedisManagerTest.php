<?php

declare(strict_types=1);

namespace Tests\Redis;

use Omega\Redis\Redis;
use Omega\Redis\RedisManager;
use RedisException;

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

function createRedisDriver(): Redis
{
    return new Redis([
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 1,
    ]);
}
