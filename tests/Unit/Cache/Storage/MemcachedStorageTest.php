<?php

declare(strict_types=1);

namespace Tests\Cache\Storage;

use DateInterval;
use Exception;
use Omega\Cache\Storage\MemcachedStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;
use stdClass;

#[CoversClass(MemcachedStorage::class)]
final class MemcachedStorageTest extends TestCase
{
    /** @var MemcachedStorage|null */
    private ?MemcachedStorage $storage;

    protected function setUp(): void
    {
        if (!MemcachedStorage::isSupported()) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        try {
            $this->storage = new MemcachedStorage([
                'ttl'    => 3600,
                'host'   => '127.0.0.1',
                'port'   => 11211,
                'prefix' => 'test_',
            ]);
            $this->storage->clear();
        } catch (Exception $e) {
            $this->markTestSkipped('Could not connect to Memcached server: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->storage)) {
            $this->storage->clear();
        }
        $this->storage = null;
    }

    /**
     * @test
     *
     * @testdox it can set and get cache
     *
     * @throws InvalidArgumentException
     */
    public function testItCanSetAndGetCache(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->assertTrue($this->storage->set('key', 'value'));
        $this->assertEquals('value', $this->storage->get('key'));
    }

    /**
     * @test
     *
     * @testdox it can get default value if key not found
     *
     * @throws InvalidArgumentException
     */
    public function testItCanGetDefaultIfKeyNotFound(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->assertEquals('default', $this->storage->get('key', 'default'));
    }

    /**
     * @test
     *
     * @testdox it can delete cache
     *
     * @throws InvalidArgumentException
     */
    public function testItCanDeleteCache(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->storage->set('key', 'value');
        $this->assertTrue($this->storage->delete('key'));
        $this->assertNull($this->storage->get('key'));
    }

    /**
     * @test
     *
     * @testdox it can clear cache
     *
     * @throws InvalidArgumentException
     */
    public function testItCanClearCache(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        $this->assertTrue($this->storage->clear());
        $this->assertNull($this->storage->get('key1'));
        $this->assertNull($this->storage->get('key2'));
    }

    /**
     * @test
     *
     * @testdox it can check if key exists
     *
     * @throws InvalidArgumentException
     */
    public function testItCanCheckIfKeyExists(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->storage->set('key', 'value');
        $this->assertTrue($this->storage->has('key'));
        $this->assertFalse($this->storage->has('not_found'));
    }

    /**
     * @test
     *
     * @testdox it can increment cache value
     *
     * @throws InvalidArgumentException
     */
    public function testItCanIncrementCache(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->storage->set('key', 10);
        $this->assertEquals(11, $this->storage->increment('key', 1));
        $this->assertEquals(11, $this->storage->get('key'));
    }

    /**
     * @test
     *
     * @testdox it can decrement cache value
     *
     * @throws InvalidArgumentException
     */
    public function testItCanDecrementCache(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->storage->set('key', 10);
        $this->assertEquals(9, $this->storage->decrement('key', 1));
        $this->assertEquals(9, $this->storage->get('key'));
    }

    /**
     * @test
     *
     * @testdox it can remember cache value
     *
     * @throws InvalidArgumentException
     */
    public function testItCanRememberCache(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $result = $this->storage->remember('key', fn () => 'value', 3600);
        $this->assertEquals('value', $result);
        $this->assertEquals('value', $this->storage->get('key'));
    }

    /**
     * @test
     *
     * @testdox it can get multiple cache values
     *
     * @throws InvalidArgumentException
     */
    public function testItCanGetMultipleCache(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');

        $results = $this->storage->getMultiple(['key1', 'key2']);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $results);
    }

    /**
     * @test
     *
     * @testdox it can set multiple cache values
     *
     * @throws InvalidArgumentException
     */
    public function testItCanSetMultipleCache(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->assertTrue($this->storage->setMultiple(['key1' => 'value1', 'key2' => 'value2'], 3600));
        $this->assertEquals('value1', $this->storage->get('key1'));
        $this->assertEquals('value2', $this->storage->get('key2'));
    }

    /**
     * @test
     *
     * @testdox it can delete multiple cache values
     *
     * @throws InvalidArgumentException
     */
    public function testItCanDeleteMultipleCache(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');

        $this->assertTrue($this->storage->deleteMultiple(['key1', 'key2']));
        $this->assertNull($this->storage->get('key1'));
        $this->assertNull($this->storage->get('key2'));
    }

    /**
     * @test
     *
     * @testdox it should not unserialize objects by default for security
     *
     * @throws InvalidArgumentException
     */
    public function testItShouldNotUnserializeObjectsByDefaultForSecurity(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $obj      = new stdClass();
        $obj->foo = 'bar';
        $this->storage->set('key', $obj);

        $result = $this->storage->get('key');

        $this->assertInstanceOf('__PHP_Incomplete_Class', $result);
    }

    /**
     * @test
     *
     * @testdox it should handle expiration using DateInterval
     *
     * @throws InvalidArgumentException
     */
    public function testItShouldHandleExpirationWithDateInterval(): void
    {
        if (null === $this->storage) {
            $this->markTestSkipped('Memcached extension is not loaded or enabled for CLI.');
        }

        $interval = new DateInterval('PT1S');
        $this->assertTrue($this->storage->set('expire_key', 'value', $interval));
        $this->assertEquals('value', $this->storage->get('expire_key'));

        sleep(2);

        $this->assertNull($this->storage->get('expire_key'));
    }
}
