<?php

declare(strict_types=1);

namespace Tests\Redis;

use Omega\Redis\Redis;
use Omega\Redis\RedisConnector;
use RedisException;

use function expect;
use function extension_loaded;

covers(Redis::class);
covers(RedisConnector::class);

beforeEach(function (): void {
    if (!extension_loaded('redis')) {
        $this->markTestSkipped('Redis extension not loaded.');
    }
});

it('can connect using persistent connection', function (): void {
    $redis = new Redis([
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'database'   => 1,
        'persistent' => true,
    ]);

    expect($redis->set('persistent_key', 'value'))->toBeTrue();
    expect($redis->get('persistent_key'))->toBe('value');

    $redis->disconnect();
});

it('can set read timeout', function (): void {
    $redis = new Redis([
        'host'         => '127.0.0.1',
        'port'         => 6379,
        'database'     => 1,
        'read_timeout' => 2.5,
    ]);

    expect($redis->client()->getOption(\Redis::OPT_READ_TIMEOUT))->toBe(2.5);

    $redis->disconnect();
});

it('throws exception on connection failure', function (): void {
    new Redis([
        'host'    => '127.0.0.1',
        'port'    => 9999,
        'timeout' => 0.1,
    ]);
})->throws(RedisException::class, 'Could not connect to Redis');

it('connects with default options when no extra configuration is provided', function (): void {
    $redis = new Redis([]);

    expect($redis->set('no-config-key', 'no-config-value'))->toBeTrue();
    expect($redis->get('no-config-key'))->toBe('no-config-value');

    $redis->del('no-config-key');
    $redis->disconnect();
});

it('ignores an empty password option', function (): void {
    $redis = new Redis(['password' => '']);

    expect($redis->set('empty-password-key', 'empty-password-value'))->toBeTrue();
    expect($redis->get('empty-password-key'))->toBe('empty-password-value');

    $redis->del('empty-password-key');
    $redis->disconnect();
});

it('throws when a unix socket connection fails', function (): void {
    new Redis(['unix_socket' => '/nonexistent/redis.sock']);
})->throws(RedisException::class, 'Could not connect to Redis');

it('throws when a persistent unix socket connection fails', function (): void {
    new Redis([
        'unix_socket'   => '/nonexistent/redis.sock',
        'persistent'    => true,
        'persistent_id' => 'test-persistent-id',
    ]);
})->throws(RedisException::class, 'Could not connect to Redis');

it('throws when authentication fails', function (): void {
    new Redis([
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 1,
        'password' => 'not-the-password',
    ]);
})->throws(RedisException::class, 'Could not connect to Redis');

it('configures the read timeout before attempting authentication', function (): void {
    new Redis([
        'host'         => '127.0.0.1',
        'port'         => 6379,
        'read_timeout' => 1.0,
        'password'     => 'not-the-password',
    ]);
})->throws(RedisException::class, 'Could not connect to Redis');

it('applies the read timeout and selects a database without authenticating', function (): void {
    $redis = new Redis([
        'host'         => '127.0.0.1',
        'port'         => 6379,
        'database'     => 2,
        'read_timeout' => 1.5,
    ]);

    expect($redis->client()->getOption(\Redis::OPT_READ_TIMEOUT))->toBe(1.5);
    expect($redis->client()->getLastError())->toBeNull();
    expect($redis->client()->getDbNum())->toBe(2);

    $redis->disconnect();
});
