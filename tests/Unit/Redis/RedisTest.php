<?php

declare(strict_types=1);

namespace Tests\Redis;

use Omega\Redis\Redis;
use Omega\Redis\RedisConnector;

use function expect;
use function extension_loaded;
use function sleep;

covers(Redis::class);
covers(RedisConnector::class);

beforeEach(function (): void {
    if (!extension_loaded('redis')) {
        $this->markTestSkipped('Redis extension not loaded.');
    }

    $this->redis = new Redis([
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 1,
    ]);
    $this->redis->flushDb();
});

afterEach(function (): void {
    if (isset($this->redis) && $this->redis) {
        $this->redis->flushDb();
        $this->redis = null;
    }
});

it('can set and get values', function (): void {
    expect($this->redis->set('test_key', 'test_value'))->toBeTrue();
    expect($this->redis->get('test_key'))->toBe('test_value');
});

it('can set a value with expiry', function (): void {
    expect($this->redis->set('expire_key', 'expire_value', 1))->toBeTrue();
    expect($this->redis->get('expire_key'))->toBe('expire_value');

    sleep(2);

    expect($this->redis->get('expire_key'))->toBeFalse();
});

it('returns false when a key does not exist', function (): void {
    expect($this->redis->get('non_existent_key'))->toBeFalse();
});

it('can run a raw command', function (): void {
    expect($this->redis->command('ping'))->toBeTrue();
});

it('can delete keys', function (): void {
    $this->redis->set('test_key', 'test_value');

    expect($this->redis->del('test_key'))->toBe(1);
    expect($this->redis->get('test_key'))->toBeFalse();
});

it('can check if a key exists', function (): void {
    $this->redis->set('test_key', 'test_value');

    expect($this->redis->exists('test_key'))->toBeTrue();
    expect($this->redis->exists('non_existent_key'))->toBeFalse();
});

it('can increment a value', function (): void {
    $this->redis->set('counter', '1');

    expect($this->redis->incr('counter'))->toBe(2);
    expect($this->redis->get('counter'))->toBe('2');
});

it('can decrement a value', function (): void {
    $this->redis->set('counter', '2');

    expect($this->redis->decr('counter'))->toBe(1);
    expect($this->redis->get('counter'))->toBe('1');
});

it('can get keys matching a pattern', function (): void {
    $this->redis->set('key1', 'value1');
    $this->redis->set('key2', 'value2');

    $keys = $this->redis->keys('key*');

    expect($keys)->toHaveCount(2);
    expect($keys)->toContain('key1');
    expect($keys)->toContain('key2');
});

it('can call redis commands magically', function (): void {
    $this->redis->hSet('hash', 'field', 'value');

    expect($this->redis->hGet('hash', 'field'))->toBe('value');
});

it('can perform hash operations', function (): void {
    $this->redis->hSet('myhash', 'field1', 'value1');
    $this->redis->hSet('myhash', 'field2', 'value2');

    expect($this->redis->hLen('myhash'))->toBe(2);
    expect($this->redis->hDel('myhash', 'field1'))->toBe(1);
    expect($this->redis->hLen('myhash'))->toBe(1);
    expect($this->redis->hGet('myhash', 'field2'))->toBe('value2');
    expect($this->redis->hGet('myhash', 'field1'))->toBeFalse();
});

it('can perform list operations', function (): void {
    $this->redis->lPush('mylist', 'item1');
    $this->redis->lPush('mylist', 'item2');

    expect($this->redis->lLen('mylist'))->toBe(2);
    expect($this->redis->rPop('mylist'))->toBe('item1');
    expect($this->redis->rPop('mylist'))->toBe('item2');
    expect($this->redis->rPop('mylist'))->toBeFalse();
    expect($this->redis->lLen('mylist'))->toBe(0);
});

it('can connect to a specific database', function (): void {
    $redis = new Redis([
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 0,
    ]);
    $redis->flushDb();

    $this->redis->set('key_db1', 'value_db1');
    $redis->set('key_db0', 'value_db0');

    expect($this->redis->get('key_db1'))->toBe('value_db1');
    expect($this->redis->get('key_db0'))->toBeFalse();
    expect($redis->get('key_db0'))->toBe('value_db0');
    expect($redis->get('key_db1'))->toBeFalse();

    $redis->flushDb();
});