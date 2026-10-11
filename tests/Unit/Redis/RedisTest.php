<?php

declare(strict_types=1);

namespace Tests\Redis;

use Omega\Redis\Redis;
use Omega\Redis\RedisConnector;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Tests\TestCase;

use function extension_loaded;
use function sleep;

#[CoversClass(Redis::class)]
#[CoversClass(RedisConnector::class)]
final class RedisTest extends TestCase
{
    private Redis $redis;

    protected function setUp(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded.');
        }

        try {
            $redis = new Redis([
                'host'     => '127.0.0.1',
                'port'     => 6379,
                'database' => 1,
            ]);
            $redis->flushDb();
        } catch (\RedisException) {
            $this->markTestSkipped('Could not connect to Redis server.');
        }

        $this->redis = $redis;
    }

    protected function tearDown(): void
    {
        if (isset($this->redis)) {
            $this->redis->flushDb();
            unset($this->redis);
        }
    }

    public function testCanSetAndGetValues(): void
    {
        $this->assertTrue($this->redis->set('test_key', 'test_value'));
        $this->assertSame('test_value', $this->redis->get('test_key'));
    }

    public function testCanSetAValueWithExpiry(): void
    {
        $this->assertTrue($this->redis->set('expire_key', 'expire_value', 1));
        $this->assertSame('expire_value', $this->redis->get('expire_key'));

        sleep(2);

        $this->assertFalse($this->redis->get('expire_key'));
    }

    public function testReturnsFalseWhenAKeyDoesNotExist(): void
    {
        $this->assertFalse($this->redis->get('non_existent_key'));
    }

    public function testCanRunARawCommand(): void
    {
        $this->assertTrue($this->redis->command('ping'));
    }

    public function testCanDeleteKeys(): void
    {
        $this->redis->set('test_key', 'test_value');

        $this->assertSame(1, $this->redis->del('test_key'));
        $this->assertFalse($this->redis->get('test_key'));
    }

    public function testCanCheckIfAKeyExists(): void
    {
        $this->redis->set('test_key', 'test_value');

        $this->assertTrue($this->redis->exists('test_key'));
        $this->assertFalse($this->redis->exists('non_existent_key'));
    }

    public function testCanIncrementAValue(): void
    {
        $this->redis->set('counter', '1');

        $this->assertSame(2, $this->redis->incr('counter'));
        $this->assertSame('2', $this->redis->get('counter'));
    }

    public function testCanDecrementAValue(): void
    {
        $this->redis->set('counter', '2');

        $this->assertSame(1, $this->redis->decr('counter'));
        $this->assertSame('1', $this->redis->get('counter'));
    }

    public function testCanGetKeysMatchingAPattern(): void
    {
        $this->redis->set('key1', 'value1');
        $this->redis->set('key2', 'value2');

        $keys = $this->redis->keys('key*');

        $this->assertCount(2, $keys);
        $this->assertContains('key1', $keys);
        $this->assertContains('key2', $keys);
    }

    public function testCanCallRedisCommandsMagically(): void
    {
        $this->redis->hSet('hash', 'field', 'value');

        $this->assertSame('value', $this->redis->hGet('hash', 'field'));
    }

    public function testCanPerformHashOperations(): void
    {
        $this->redis->hSet('myhash', 'field1', 'value1');
        $this->redis->hSet('myhash', 'field2', 'value2');

        $this->assertSame(2, $this->redis->hLen('myhash'));
        $this->assertSame(1, $this->redis->hDel('myhash', 'field1'));
        $this->assertSame(1, $this->redis->hLen('myhash'));
        $this->assertSame('value2', $this->redis->hGet('myhash', 'field2'));
        $this->assertFalse($this->redis->hGet('myhash', 'field1'));
    }

    public function testCanPerformListOperations(): void
    {
        $this->redis->lPush('mylist', 'item1');
        $this->redis->lPush('mylist', 'item2');

        $this->assertSame(2, $this->redis->lLen('mylist'));
        $this->assertSame('item1', $this->redis->rPop('mylist'));
        $this->assertSame('item2', $this->redis->rPop('mylist'));
        $this->assertFalse($this->redis->rPop('mylist'));
        $this->assertSame(0, $this->redis->lLen('mylist'));
    }

    public function testCanConnectToASpecificDatabase(): void
    {
        $redis = new Redis([
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 0,
        ]);
        $redis->flushDb();

        $this->redis->set('key_db1', 'value_db1');
        $redis->set('key_db0', 'value_db0');

        $this->assertSame('value_db1', $this->redis->get('key_db1'));
        $this->assertFalse($this->redis->get('key_db0'));
        $this->assertSame('value_db0', $redis->get('key_db0'));
        $this->assertFalse($redis->get('key_db1'));

        $redis->flushDb();
    }

    public function testReturnsAnEmptyArrayWhenRedisDoesNotReportKeysAsAnArray(): void
    {
        $mock = $this->createStub(\Redis::class);
        $mock->method('keys')->willReturn(false);

        $redis = new Redis([
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 1,
        ]);

        $property = new ReflectionProperty(Redis::class, 'redis');
        $property->setValue($redis, $mock);

        $this->assertSame([], $redis->keys('*'));
    }
}
