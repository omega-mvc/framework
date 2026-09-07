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