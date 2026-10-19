<?php

declare(strict_types=1);

namespace Tests\Cache\Storage;

use DateInterval;
use Exception;
use InvalidArgumentException;
use Omega\Cache\Storage\RedisStorage;
use Omega\Redis\Redis;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\TestCase;

#[CoversClass(Redis::class)]
#[CoversClass(RedisStorage::class)]
final class RedisStorageTest extends TestCase
{
    public function testSetsAndGetsACacheValue(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $this->assertTrue($storage->set('key', 'value'));
        $this->assertEquals('value', $storage->get('key'));
    }

    public function testGetsTheDefaultValueWhenTheKeyIsNotFound(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $this->assertEquals('default', $storage->get('key', 'default'));
    }

    public function testDeletesACacheValue(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $storage->set('key', 'value');
        $this->assertTrue($storage->delete('key'));
        $this->assertNull($storage->get('key'));
    }

    public function testClearsTheCache(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $storage->set('key1', 'value1');
        $storage->set('key2', 'value2');
        $this->assertTrue($storage->clear());
        $this->assertNull($storage->get('key1'));
        $this->assertNull($storage->get('key2'));
    }

    public function testChecksWhetherAKeyExists(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $storage->set('key', 'value');
        $this->assertTrue($storage->has('key'));
        $this->assertFalse($storage->has('not_found'));
    }

    public function testIncrementsACacheValue(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $storage->set('key', 10);
        $this->assertEquals(11, $storage->increment('key', 1));
        $this->assertEquals(11, $storage->get('key'));
    }

    public function testDecrementsACacheValue(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $storage->set('key', 10);
        $this->assertEquals(9, $storage->decrement('key', 1));
        $this->assertEquals(9, $storage->get('key'));
    }

    public function testRemembersACacheValue(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $result = $storage->remember('key', fn () => 'value', 3600);
        $this->assertEquals('value', $result);
        $this->assertEquals('value', $storage->get('key'));
    }

    public function testGetsMultipleCacheValues(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $storage->set('key1', 'value1');
        $storage->set('key2', 'value2');

        $results = $storage->getMultiple(['key1', 'key2']);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $results);
    }

    public function testSetsMultipleCacheValues(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $this->assertTrue($storage->setMultiple(['key1' => 'value1', 'key2' => 'value2'], 3600));
        $this->assertEquals('value1', $storage->get('key1'));
        $this->assertEquals('value2', $storage->get('key2'));
    }

    public function testDeletesMultipleCacheValues(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $storage->set('key1', 'value1');
        $storage->set('key2', 'value2');

        $this->assertTrue($storage->deleteMultiple(['key1', 'key2']));
        $this->assertNull($storage->get('key1'));
        $this->assertNull($storage->get('key2'));
    }

    public function testDoesNotUnserializeObjectsByDefaultForSecurity(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $obj      = new stdClass();
        $obj->foo = 'bar';
        $storage->set('key', $obj);

        $result = $storage->get('key');

        $this->assertInstanceOf('__PHP_Incomplete_Class', $result);
    }

    public function testHandlesExpirationUsingADateInterval(): void
    {
        $storage = self::cacheRedisStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Redis server');
        }

        $interval = new DateInterval('PT1S');
        $this->assertTrue($storage->set('expire_key', 'value', $interval));
        $this->assertEquals('value', $storage->get('expire_key'));

        sleep(2);

        $this->assertNull($storage->get('expire_key'));
    }

    public function testReturnsTheDefaultWhenAStoredValueIsNotAString(): void
    {
        $storage = new RedisStorage(['ttl' => 3600], new FakeRedisConnection(['key' => 42]));

        $this->assertSame('default', $storage->get('key', 'default'));
    }

    public function testInitializesAMissingCounterOnIncrement(): void
    {
        $redis   = new FakeRedisConnection();
        $storage = new RedisStorage(['ttl' => 3600], $redis);

        $this->assertSame(3, $storage->increment('counter', 3));
        $this->assertContains(['method' => 'set', 'arguments' => ['counter', 'i:3;', 3600]], $redis->calls);
    }

    public function testAppliesADateIntervalDefaultTtlWhenIncrementingAMissingKey(): void
    {
        $redis   = new FakeRedisConnection();
        $storage = new RedisStorage(['ttl' => new DateInterval('PT1M')], $redis);

        $this->assertSame(1, $storage->increment('counter', 1));
    }

    public function testThrowsWhenIncrementingANonIntegerValue(): void
    {
        $redis   = new FakeRedisConnection(['counter' => 's:3:"abc";']);
        $storage = new RedisStorage(['ttl' => 3600], $redis);

        $redis->existsResult = true;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value to increment must be an integer.');

        $storage->increment('counter', 1);
    }

    public function testReturnsTheCachedValueFromRememberWithoutInvokingTheCallback(): void
    {
        $redis   = new FakeRedisConnection(['key' => 's:5:"hello";']);
        $storage = new RedisStorage(['ttl' => 3600], $redis);

        $called = false;

        $result = $storage->remember('key', function () use (&$called): string {
            $called = true;

            return 'ignored';
        }, 3600);

        $this->assertSame('hello', $result);
        $this->assertFalse($called);
    }

    public function testReportsAPartialSetFailure(): void
    {
        $redis            = new FakeRedisConnection();
        $redis->setResult = false;
        $storage          = new RedisStorage(['ttl' => 3600], $redis);

        $this->assertFalse($storage->setMultiple(['a' => 1, 'b' => 2]));
    }

    public function testReportsAPartialDeleteFailure(): void
    {
        $redis            = new FakeRedisConnection();
        $redis->delResult = 0;
        $storage          = new RedisStorage(['ttl' => 3600], $redis);

        $this->assertFalse($storage->deleteMultiple(['a', 'b']));
    }

    public function testHandlesEmptyIterablesForGetSetAndDeleteMultiple(): void
    {
        $storage = new RedisStorage(['ttl' => 3600], new FakeRedisConnection());

        $this->assertSame([], $storage->getMultiple([]));
        $this->assertTrue($storage->setMultiple([]));
        $this->assertTrue($storage->deleteMultiple([]));
    }

    public function testReturnsEmptyInfoAndZeroExpirationFromTheBaseImplementation(): void
    {
        $storage = self::cacheRedisStorage();
        if (null === $storage) {
            $this->markTestSkipped('Redis is not available.');
        }

        $this->assertSame([], $storage->getInfo('missing-key'));
        $this->assertSame(0, $storage->calculateExpirationTimestamp(null));
    }

    private static function cacheRedisStorage(): ?RedisStorage
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
}
