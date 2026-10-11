<?php

declare(strict_types=1);

namespace Tests\Redis;

use Omega\Redis\Redis;
use Omega\Redis\RedisConnector;
use PHPUnit\Framework\Attributes\CoversClass;
use RedisException;
use Tests\TestCase;

use function extension_loaded;
use function fclose;
use function fsockopen;
use function restore_error_handler;
use function set_error_handler;

#[CoversClass(Redis::class)]
#[CoversClass(RedisConnector::class)]
final class RedisConnectionTest extends TestCase
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

    public function testCanConnectUsingPersistentConnection(): void
    {
        $redis = new Redis([
            'host'       => '127.0.0.1',
            'port'       => 6379,
            'database'   => 1,
            'persistent' => true,
        ]);

        $this->assertTrue($redis->set('persistent_key', 'value'));
        $this->assertSame('value', $redis->get('persistent_key'));

        $redis->disconnect();
    }

    public function testCanSetReadTimeout(): void
    {
        $redis = new Redis([
            'host'         => '127.0.0.1',
            'port'         => 6379,
            'database'     => 1,
            'read_timeout' => 2.5,
        ]);

        $this->assertSame(2.5, $redis->client()->getOption(\Redis::OPT_READ_TIMEOUT));

        $redis->disconnect();
    }

    public function testThrowsExceptionOnConnectionFailure(): void
    {
        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('Could not connect to Redis');

        new Redis([
            'host'    => '127.0.0.1',
            'port'    => 9999,
            'timeout' => 0.1,
        ]);
    }

    public function testConnectsWithDefaultOptionsWhenNoExtraConfigurationIsProvided(): void
    {
        $redis = new Redis([]);

        $this->assertTrue($redis->set('no-config-key', 'no-config-value'));
        $this->assertSame('no-config-value', $redis->get('no-config-key'));

        $redis->del('no-config-key');
        $redis->disconnect();
    }

    public function testIgnoresAnEmptyPasswordOption(): void
    {
        $redis = new Redis(['password' => '']);

        $this->assertTrue($redis->set('empty-password-key', 'empty-password-value'));
        $this->assertSame('empty-password-value', $redis->get('empty-password-key'));

        $redis->del('empty-password-key');
        $redis->disconnect();
    }

    public function testThrowsWhenAUnixSocketConnectionFails(): void
    {
        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('Could not connect to Redis');

        new Redis(['unix_socket' => '/nonexistent/redis.sock']);
    }

    public function testThrowsWhenAPersistentUnixSocketConnectionFails(): void
    {
        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('Could not connect to Redis');

        new Redis([
            'unix_socket'   => '/nonexistent/redis.sock',
            'persistent'    => true,
            'persistent_id' => 'test-persistent-id',
        ]);
    }

    public function testThrowsWhenAuthenticationFails(): void
    {
        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('Could not connect to Redis');

        new Redis([
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 1,
            'password' => 'not-the-password',
        ]);
    }

    public function testConfiguresTheReadTimeoutBeforeAttemptingAuthentication(): void
    {
        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('Could not connect to Redis');

        new Redis([
            'host'         => '127.0.0.1',
            'port'         => 6379,
            'read_timeout' => 1.0,
            'password'     => 'not-the-password',
        ]);
    }

    public function testAppliesTheReadTimeoutAndSelectsADatabaseWithoutAuthenticating(): void
    {
        $redis = new Redis([
            'host'         => '127.0.0.1',
            'port'         => 6379,
            'database'     => 2,
            'read_timeout' => 1.5,
        ]);

        $this->assertSame(1.5, $redis->client()->getOption(\Redis::OPT_READ_TIMEOUT));
        $this->assertNull($redis->client()->getLastError());
        $this->assertSame(2, $redis->client()->getDbNum());

        $redis->disconnect();
    }
}
