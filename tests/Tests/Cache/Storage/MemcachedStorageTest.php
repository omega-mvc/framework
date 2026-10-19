<?php

declare(strict_types=1);

namespace Tests\Cache\Storage;

use DateInterval;
use Exception;
use InvalidArgumentException;
use Omega\Cache\Storage\MemcachedStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\TestCase;

#[CoversClass(MemcachedStorage::class)]
final class MemcachedStorageTest extends TestCase
{
    public function testSetsAndGetsACacheValue(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $this->assertTrue($storage->set('key', 'value'));
        $this->assertEquals('value', $storage->get('key'));
    }

    public function testGetsTheDefaultValueWhenTheKeyIsNotFound(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $this->assertEquals('default', $storage->get('key', 'default'));
    }

    public function testDeletesACacheValue(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage->set('key', 'value');
        $this->assertTrue($storage->delete('key'));
        $this->assertNull($storage->get('key'));
    }

    public function testClearsTheCache(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage->set('key1', 'value1');
        $storage->set('key2', 'value2');
        $this->assertTrue($storage->clear());
        $this->assertNull($storage->get('key1'));
        $this->assertNull($storage->get('key2'));
    }

    public function testChecksWhetherAKeyExists(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage->set('key', 'value');
        $this->assertTrue($storage->has('key'));
        $this->assertFalse($storage->has('not_found'));
    }

    public function testIncrementsACacheValue(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage->set('key', 10);
        $this->assertEquals(11, $storage->increment('key', 1));
        $this->assertEquals(11, $storage->get('key'));
    }

    public function testDecrementsACacheValue(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage->set('key', 10);
        $this->assertEquals(9, $storage->decrement('key', 1));
        $this->assertEquals(9, $storage->get('key'));
    }

    public function testRemembersACacheValue(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $result = $storage->remember('key', fn () => 'value', 3600);
        $this->assertEquals('value', $result);
        $this->assertEquals('value', $storage->get('key'));
    }

    public function testGetsMultipleCacheValues(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage->set('key1', 'value1');
        $storage->set('key2', 'value2');

        $results = $storage->getMultiple(['key1', 'key2']);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $results);
    }

    public function testSetsMultipleCacheValues(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $this->assertTrue($storage->setMultiple(['key1' => 'value1', 'key2' => 'value2'], 3600));
        $this->assertEquals('value1', $storage->get('key1'));
        $this->assertEquals('value2', $storage->get('key2'));
    }

    public function testDeletesMultipleCacheValues(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage->set('key1', 'value1');
        $storage->set('key2', 'value2');

        $this->assertTrue($storage->deleteMultiple(['key1', 'key2']));
        $this->assertNull($storage->get('key1'));
        $this->assertNull($storage->get('key2'));
    }

    public function testDoesNotUnserializeObjectsByDefaultForSecurity(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $obj      = new stdClass();
        $obj->foo = 'bar';
        $storage->set('key', $obj);

        $result = $storage->get('key');

        $this->assertInstanceOf('__PHP_Incomplete_Class', $result);
    }

    public function testHandlesExpirationUsingADateInterval(): void
    {
        $storage = self::cacheMemcachedStorage();

        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $interval = new DateInterval('PT1S');
        $this->assertTrue($storage->set('expire_key', 'value', $interval));
        $this->assertEquals('value', $storage->get('expire_key'));

        sleep(2);

        $this->assertNull($storage->get('expire_key'));
    }

    private static function cacheMemcachedStorage(): ?MemcachedStorage
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

    public function testUsesTheDefaultHostAndPortWhenNotProvided(): void
    {
        $storage = new MemcachedStorage(['ttl' => 3600]);

        $this->assertInstanceOf(MemcachedStorage::class, $storage);
    }

    public function testSetsTheConnectTimeoutOption(): void
    {
        $storage = new MemcachedStorage([
            'ttl'     => 3600,
            'host'    => '127.0.0.1',
            'port'    => 11211,
            'timeout' => 1,
        ]);

        $this->assertInstanceOf(MemcachedStorage::class, $storage);
    }

    public function testAcceptsAServersListWithSaslCredentials(): void
    {
        set_error_handler(static fn (): bool => true);
        try {
            $storage = new MemcachedStorage([
                'ttl'      => 3600,
                'servers'  => [['host' => '127.0.0.1', 'port' => 11211]],
                'username' => 'user',
                'password' => 'pass',
            ]);
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(MemcachedStorage::class, $storage);
    }

    public function testReturnsTheDefaultValueWhenTheStoredValueIsNotAString(): void
    {
        $storage = self::cacheMemcachedStorage();
        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $client = new \Memcached();
        $client->addServer('127.0.0.1', 11211);
        $client->set('test_non_string_key', ['not', 'a', 'string']);

        $this->assertSame('default', $storage->get('non_string_key', 'default'));
    }

    public function testIncrementsAValueThatDoesNotExistYet(): void
    {
        $storage = self::cacheMemcachedStorage();
        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage->delete('increment-missing');

        $this->assertSame(7, $storage->increment('increment-missing', 7));
    }

    public function testThrowsWhenIncrementingANonIntegerValue(): void
    {
        $storage = self::cacheMemcachedStorage();
        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage->set('increment-string', 'not-an-integer');

        $this->expectException(InvalidArgumentException::class);

        $storage->increment('increment-string', 1);
    }

    public function testRemembersAValueOnASecondCall(): void
    {
        $storage = self::cacheMemcachedStorage();
        if (null === $storage) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $first  = $storage->remember('remember-key', fn (): string => 'first', 3600);
        $second = $storage->remember('remember-key', fn (): string => 'second', 3600);

        $this->assertSame('first', $first);
        $this->assertSame('first', $second);
    }

    public function testUsesADateIntervalAsTheDefaultTtl(): void
    {
        if (!MemcachedStorage::isSupported()) {
            $this->markTestSkipped('Could not connect to Memcached server');
        }

        $storage = new MemcachedStorage([
            'ttl'    => new DateInterval('PT1H'),
            'host'   => '127.0.0.1',
            'port'   => 11211,
            'prefix' => 'test_',
        ]);

        $this->assertTrue($storage->set('interval-key', 'value'));

        $storage->clear();
    }
}
