<?php

declare(strict_types=1);

namespace Tests\Cache\Storage;

use DateInterval;
use Exception;
use InvalidArgumentException;
use Omega\Cache\Storage\RedisStorage;
use Omega\Redis\Redis;
use stdClass;

covers(
    Redis::class,
    RedisStorage::class,
);

it('sets and gets a cache value', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    expect($storage->set('key', 'value'))->toBeTrue();
    expect($storage->get('key'))->toEqual('value');
});

it('gets the default value when the key is not found', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    expect($storage->get('key', 'default'))->toEqual('default');
});

it('deletes a cache value', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $storage->set('key', 'value');
    expect($storage->delete('key'))->toBeTrue();
    expect($storage->get('key'))->toBeNull();
});

it('clears the cache', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $storage->set('key1', 'value1');
    $storage->set('key2', 'value2');
    expect($storage->clear())->toBeTrue();
    expect($storage->get('key1'))->toBeNull();
    expect($storage->get('key2'))->toBeNull();
});

it('checks whether a key exists', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $storage->set('key', 'value');
    expect($storage->has('key'))->toBeTrue();
    expect($storage->has('not_found'))->toBeFalse();
});

it('increments a cache value', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $storage->set('key', 10);
    expect($storage->increment('key', 1))->toEqual(11);
    expect($storage->get('key'))->toEqual(11);
});

it('decrements a cache value', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $storage->set('key', 10);
    expect($storage->decrement('key', 1))->toEqual(9);
    expect($storage->get('key'))->toEqual(9);
});

it('remembers a cache value', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $result = $storage->remember('key', fn () => 'value', 3600);
    expect($result)->toEqual('value');
    expect($storage->get('key'))->toEqual('value');
});

it('gets multiple cache values', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $storage->set('key1', 'value1');
    $storage->set('key2', 'value2');

    $results = $storage->getMultiple(['key1', 'key2']);
    expect($results)->toEqual(['key1' => 'value1', 'key2' => 'value2']);
});

it('sets multiple cache values', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    expect($storage->setMultiple(['key1' => 'value1', 'key2' => 'value2'], 3600))->toBeTrue();
    expect($storage->get('key1'))->toEqual('value1');
    expect($storage->get('key2'))->toEqual('value2');
});

it('deletes multiple cache values', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $storage->set('key1', 'value1');
    $storage->set('key2', 'value2');

    expect($storage->deleteMultiple(['key1', 'key2']))->toBeTrue();
    expect($storage->get('key1'))->toBeNull();
    expect($storage->get('key2'))->toBeNull();
});

it('does not unserialize objects by default for security', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $obj      = new stdClass();
    $obj->foo = 'bar';
    $storage->set('key', $obj);

    $result = $storage->get('key');

    expect($result)->toBeInstanceOf('__PHP_Incomplete_Class');
});

it('handles expiration using a DateInterval', function (): void {
    $storage = cache_redis_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Redis server');

    }

    $interval = new DateInterval('PT1S');
    expect($storage->set('expire_key', 'value', $interval))->toBeTrue();
    expect($storage->get('expire_key'))->toEqual('value');

    sleep(2);

    expect($storage->get('expire_key'))->toBeNull();
});

it('returns the default when a stored value is not a string', function (): void {
    $storage = new RedisStorage(['ttl' => 3600], new FakeRedisConnection(['key' => 42]));

    expect($storage->get('key', 'default'))->toBe('default');
});

it('initializes a missing counter on increment', function (): void {
    $redis   = new FakeRedisConnection();
    $storage = new RedisStorage(['ttl' => 3600], $redis);

    expect($storage->increment('counter', 3))->toBe(3);
    expect($redis->calls)->toContain(['method' => 'set', 'arguments' => ['counter', 'i:3;', 3600]]);
});

it('applies a DateInterval default ttl when incrementing a missing key', function (): void {
    $redis   = new FakeRedisConnection();
    $storage = new RedisStorage(['ttl' => new DateInterval('PT1M')], $redis);

    expect($storage->increment('counter', 1))->toBe(1);
});

it('throws when incrementing a non-integer value', function (): void {
    $redis   = new FakeRedisConnection(['counter' => 's:3:"abc";']);
    $storage = new RedisStorage(['ttl' => 3600], $redis);

    $redis->existsResult = true;

    expect(fn () => $storage->increment('counter', 1))
        ->toThrow(InvalidArgumentException::class, 'Value to increment must be an integer.');
});

it('returns the cached value from remember without invoking the callback', function (): void {
    $redis   = new FakeRedisConnection(['key' => 's:5:"hello";']);
    $storage = new RedisStorage(['ttl' => 3600], $redis);

    $called = false;

    $result = $storage->remember('key', function () use (&$called): string {
        $called = true;

        return 'ignored';
    }, 3600);

    expect($result)->toBe('hello');
    expect($called)->toBeFalse();
});

it('reports a partial set failure', function (): void {
    $redis           = new FakeRedisConnection();
    $redis->setResult = false;
    $storage         = new RedisStorage(['ttl' => 3600], $redis);

    expect($storage->setMultiple(['a' => 1, 'b' => 2]))->toBeFalse();
});

it('reports a partial delete failure', function (): void {
    $redis           = new FakeRedisConnection();
    $redis->delResult = 0;
    $storage         = new RedisStorage(['ttl' => 3600], $redis);

    expect($storage->deleteMultiple(['a', 'b']))->toBeFalse();
});

it('handles empty iterables for get, set and delete multiple', function (): void {
    $storage = new RedisStorage(['ttl' => 3600], new FakeRedisConnection());

    expect($storage->getMultiple([]))->toBe([]);
    expect($storage->setMultiple([]))->toBeTrue();
    expect($storage->deleteMultiple([]))->toBeTrue();
});

function cache_redis_storage(): ?RedisStorage
{
    if (!RedisStorage::isSupported()) {
        return null;
    }

    try {
        $redis = new Redis([
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 2,
        ]);
        $redis->command('ping');
    } catch (Exception) {
        return null;
    }

    $redis->flushdb();

    return new RedisStorage(['ttl' => 3600], $redis);
}