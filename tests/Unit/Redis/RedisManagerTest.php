<?php

declare(strict_types=1);

namespace Tests\Redis;

use Exception;
use Omega\Redis\Redis;
use Omega\Redis\RedisManager;
use PHPUnit\Framework\Attributes\CoversClass;
use RedisException;
use ReflectionProperty;
use Tests\TestCase;

use function extension_loaded;
use function fclose;
use function file_exists;
use function fsockopen;
use function restore_error_handler;
use function set_error_handler;

#[CoversClass(Redis::class)]
#[CoversClass(RedisManager::class)]
final class RedisManagerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded.');
        }

        $socket = false;
        set_error_handler(static fn (): bool => true);
        try {
            $socket = fsockopen('127.0.0.1', 6379, $errno, $errstr, 1.0);
        } finally {
            restore_error_handler();
        }
        if (false === $socket) {
            $this->markTestSkipped('Redis server is not reachable: ' . $errstr);
        }
        fclose($socket);
    }

    protected function tearDown(): void
    {
        if (!extension_loaded('redis')) {
            return;
        }

        // tearDown() can run even when setUp() skipped the test;
        // do not let flushDb() connect to a missing server.
        $socket = false;
        set_error_handler(static fn (): bool => true);
        try {
            $socket = fsockopen('127.0.0.1', 6379, $errno, $errstr, 1.0);
        } finally {
            restore_error_handler();
        }
        if (false === $socket) {
            return;
        }
        fclose($socket);

        self::createRedisDriver()->flushDb();
    }

    public function testCanSetAndGetDefaultDriver(): void
    {
        $manager = new RedisManager();
        $driver  = self::createRedisDriver();

        $manager->setDefaultDriver($driver);

        $this->assertSame('PHPRedis', $manager->driver()->getName());
        $this->assertSame($driver, $manager->driver());

        $manager->set('manager-key', 'manager-value');

        $this->assertSame('manager-value', $driver->get('manager-key'));
        $this->assertSame('manager-value', $manager->get('manager-key'));
    }

    public function testCanSetAndGetNamedDrivers(): void
    {
        $manager = new RedisManager();

        $defaultDriver = self::createRedisDriver();
        $manager->setDefaultDriver($defaultDriver);
        $manager->set('default-key', 'default-value');

        $namedDriver = new Redis([
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 2,
        ]);
        $manager->setDriver('second', $namedDriver);

        $this->assertSame('PHPRedis', $manager->driver('second')->getName());
        $this->assertSame($namedDriver, $manager->driver('second'));

        $manager->driver('second')->set('named-key', 'named-value');

        $this->assertSame('named-value', $manager->driver('second')->get('named-key'));
        $this->assertSame('default-value', $manager->get('default-key'));
        $this->assertFalse($manager->get('named-key'));
        $this->assertFalse($manager->driver('second')->get('default-key'));
    }

    public function testCanUseClosureAsDriver(): void
    {
        $manager = new RedisManager();
        $manager->setDriver('lazy', function (): Redis {
            return self::createRedisDriver();
        });

        $this->assertSame('PHPRedis', $manager->driver('lazy')->getName());

        $manager->driver('lazy')->set('lazy-key', 'lazy-value');

        $this->assertSame('lazy-value', $manager->driver('lazy')->get('lazy-key'));
    }

    public function testCanConnectViaUnixSocket(): void
    {
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

        $this->assertTrue($manager->set('socket-key', 'socket-value'));
        $this->assertSame('socket-value', $manager->get('socket-key'));
    }

    public function testCanConnectViaUnixSocketWithoutExtraOptions(): void
    {
        $socketPath = '/var/run/redis/redis.sock';

        if (false === file_exists($socketPath)) {
            $this->markTestSkipped("Redis socket not found at {$socketPath}.");
        }

        try {
            $driver = new Redis(['unix_socket' => $socketPath]);
        } catch (RedisException $e) {
            $this->markTestSkipped("Redis socket unreachable at {$socketPath}: {$e->getMessage()}");
        }

        $this->assertTrue($driver->set('socket-plain-key', 'socket-plain-value'));
        $this->assertSame('socket-plain-value', $driver->get('socket-plain-key'));

        $driver->del('socket-plain-key');
    }

    public function testCanConnectViaUnixSocketWithAReadTimeout(): void
    {
        $socketPath = '/var/run/redis/redis.sock';

        if (false === file_exists($socketPath)) {
            $this->markTestSkipped("Redis socket not found at {$socketPath}.");
        }

        try {
            $driver = new Redis([
                'unix_socket'  => $socketPath,
                'database'     => 1,
                'read_timeout' => 2.5,
            ]);
        } catch (RedisException $e) {
            $this->markTestSkipped("Redis socket unreachable at {$socketPath}: {$e->getMessage()}");
        }

        $this->assertSame(2.5, $driver->client()->getOption(\Redis::OPT_READ_TIMEOUT));
    }

    public function testResolvesTheDefaultConnectionFromConfigWhenNoDefaultDriverIsSet(): void
    {
        $manager = new RedisManager();
        $manager->setConfig([
            'default'     => 'default',
            'connections' => [
                'default' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 1],
            ],
        ]);

        $driver = $manager->driver();

        $this->assertInstanceOf(Redis::class, $driver);
        $this->assertSame('PHPRedis', $driver->getName());
        $this->assertSame($driver, $manager->driver());
    }

    public function testResolvesANamedConnectionFromConfigAndCachesIt(): void
    {
        $manager = new RedisManager();
        $manager->setConfig([
            'default'     => 'fallback',
            'connections' => [
                'fallback' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 1],
                'named'    => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 2],
            ],
        ]);

        $driver = $manager->connection('named');

        $this->assertInstanceOf(Redis::class, $driver);
        $this->assertSame($driver, $manager->connection('named'));
    }

    public function testFallsBackToTheDefaultConnectionForAnUnknownNamedDriver(): void
    {
        $manager = new RedisManager();
        $manager->setConfig([
            'default'     => 'default',
            'connections' => [
                'default' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 1],
            ],
        ]);

        $this->assertInstanceOf(Redis::class, $manager->driver('not-registered'));
    }

    public function testThrowsWhenANamedConnectionIsNotConfigured(): void
    {
        $manager = new RedisManager();
        $manager->setConfig([
            'default'     => 'default',
            'connections' => [
                'default' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 1],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Can not use connection unknown.');

        $manager->connection('unknown');
    }

    public function testThrowsWhenNoDefaultConnectionNameIsConfigured(): void
    {
        $manager = new RedisManager();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No default Redis connection has been configured.');

        $manager->driver();
    }

    public function testThrowsWhenARegisteredDriverCannotBeResolved(): void
    {
        $manager = new RedisManager();

        $property = new ReflectionProperty(RedisManager::class, 'driver');
        $property->setValue($manager, ['broken' => 'not-a-driver']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Can not use driver broken.');

        $manager->driver('broken');
    }

    public function testResolvesANamedConnectionRegisteredAsADriver(): void
    {
        $manager = new RedisManager();

        $manager->setDriver('connection-a', fn (): Redis => self::createRedisDriver());

        $this->assertInstanceOf(Redis::class, $manager->connection('connection-a'));
    }

    public function testDelegatesRedisOperationsToTheDefaultDriver(): void
    {
        $manager = new RedisManager();
        $manager->setDefaultDriver(self::createRedisDriver());

        $this->assertTrue($manager->set('delegated-key', 'delegated-value'));
        $this->assertSame('delegated-value', $manager->get('delegated-key'));

        $this->assertSame(1, $manager->del('delegated-key'));
        $this->assertFalse($manager->exists('delegated-key'));

        $this->assertSame(1, $manager->incr('delegated-counter'));
        $this->assertSame(0, $manager->decr('delegated-counter'));

        $manager->set('delegated-pattern-1', 'a');
        $manager->set('delegated-pattern-2', 'b');
        $this->assertCount(2, $manager->keys('delegated-pattern-*'));

        $this->assertSame('PHPRedis', $manager->getName());
        $this->assertInstanceOf(\Redis::class, $manager->client());
        $this->assertTrue($manager->command('ping'));

        $manager->__call('hSet', ['delegated-hash', 'field', 'value']);
        $this->assertSame('value', $manager->__call('hGet', ['delegated-hash', 'field']));

        $manager->disconnect();
    }

    private static function createRedisDriver(): Redis
    {
        return new Redis([
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 1,
        ]);
    }
}
