<?php

declare(strict_types=1);

namespace Tests\Cache\Storage;

use DateInterval;
use Exception;
use Omega\Cache\Storage\MemcachedStorage;
use stdClass;

covers(MemcachedStorage::class);

it('sets and gets a cache value', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    expect($storage->set('key', 'value'))->toBeTrue();
    expect($storage->get('key'))->toEqual('value');
});

it('gets the default value when the key is not found', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    expect($storage->get('key', 'default'))->toEqual('default');
});

it('deletes a cache value', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $storage->set('key', 'value');
    expect($storage->delete('key'))->toBeTrue();
    expect($storage->get('key'))->toBeNull();
});

it('clears the cache', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $storage->set('key1', 'value1');
    $storage->set('key2', 'value2');
    expect($storage->clear())->toBeTrue();
    expect($storage->get('key1'))->toBeNull();
    expect($storage->get('key2'))->toBeNull();
});

it('checks whether a key exists', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $storage->set('key', 'value');
    expect($storage->has('key'))->toBeTrue();
    expect($storage->has('not_found'))->toBeFalse();
});

it('increments a cache value', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $storage->set('key', 10);
    expect($storage->increment('key', 1))->toEqual(11);
    expect($storage->get('key'))->toEqual(11);
});

it('decrements a cache value', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $storage->set('key', 10);
    expect($storage->decrement('key', 1))->toEqual(9);
    expect($storage->get('key'))->toEqual(9);
});

it('remembers a cache value', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $result = $storage->remember('key', fn () => 'value', 3600);
    expect($result)->toEqual('value');
    expect($storage->get('key'))->toEqual('value');
});

it('gets multiple cache values', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $storage->set('key1', 'value1');
    $storage->set('key2', 'value2');

    $results = $storage->getMultiple(['key1', 'key2']);
    expect($results)->toEqual(['key1' => 'value1', 'key2' => 'value2']);
});

it('sets multiple cache values', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    expect($storage->setMultiple(['key1' => 'value1', 'key2' => 'value2'], 3600))->toBeTrue();
    expect($storage->get('key1'))->toEqual('value1');
    expect($storage->get('key2'))->toEqual('value2');
});

it('deletes multiple cache values', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $storage->set('key1', 'value1');
    $storage->set('key2', 'value2');

    expect($storage->deleteMultiple(['key1', 'key2']))->toBeTrue();
    expect($storage->get('key1'))->toBeNull();
    expect($storage->get('key2'))->toBeNull();
});

it('does not unserialize objects by default for security', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $obj      = new stdClass();
    $obj->foo = 'bar';
    $storage->set('key', $obj);

    $result = $storage->get('key');

    expect($result)->toBeInstanceOf('__PHP_Incomplete_Class');
});

it('handles expiration using a DateInterval', function (): void {
    $storage = cache_memcached_storage();

    if (null === $storage) {
        $this->markTestSkipped('Could not connect to Memcached server');

    }

    $interval = new DateInterval('PT1S');
    expect($storage->set('expire_key', 'value', $interval))->toBeTrue();
    expect($storage->get('expire_key'))->toEqual('value');

    sleep(2);

    expect($storage->get('expire_key'))->toBeNull();
});

function cache_memcached_storage(): ?MemcachedStorage
{
    if (!MemcachedStorage::isSupported()) {
        return null;
    }

    try {
        $storage = new MemcachedStorage([
            'ttl'    => 3600,
            'host'   => '127.0.0.1',
            'port'   => 11211,
            'prefix' => 'test_',
        ]);
        $storage->clear();

        return $storage;
    } catch (Exception) {
        return null;
    }
}