<?php

declare(strict_types=1);

namespace Tests\Redis;

use Exception;
use Omega\Application\Application;
use Omega\Application\ApplicationInterface;
use Omega\Redis\RedisInterface;
use Omega\Redis\RedisManager;
use Omega\Redis\RedisServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function extension_loaded;

#[CoversClass(RedisServiceProvider::class)]
final class RedisServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->app = new Application('/');
    }

    protected function tearDown(): void
    {
        $this->app->flush();
    }

    public function testRegistersTheManagerAndResolvesAConfiguredConnection(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded.');
        }

        $this->app->set('config', [
            'redis' => [
                'default'     => 'default',
                'connections' => [
                    'default' => [
                        'host'     => '127.0.0.1',
                        'port'     => 6379,
                        'database' => 1,
                    ],
                ],
            ],
        ]);

        (new RedisServiceProvider($this->app))->register();

        $manager = $this->app->get(RedisManager::class);
        $this->assertInstanceOf(RedisManager::class, $manager);

        try {
            $redis = self::resolveRedisInterface($this->app);
        } catch (Exception) {
            $this->markTestSkipped('Could not connect to Redis server.');
        }

        $this->assertSame('PHPRedis', $redis->getName());
        $this->assertTrue($redis->set('provider-key', 'provider-value'));
        $this->assertSame('provider-value', $redis->get('provider-key'));

        $this->assertSame(1, $redis->del('provider-key'));
    }

    public function testDefersTheConnectionWhenNoRedisConfigurationIsRegistered(): void
    {
        $this->app->set('config', []);

        (new RedisServiceProvider($this->app))->register();

        $this->assertInstanceOf(RedisManager::class, $this->app->get(RedisManager::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No default Redis connection has been configured.');

        $this->app->get(RedisInterface::class);
    }

    /**
     * Resolve the Redis connection from the application container.
     *
     * @param ApplicationInterface $app The application instance.
     * @return RedisInterface The resolved Redis connection.
     */
    private static function resolveRedisInterface(ApplicationInterface $app): RedisInterface
    {
        $redis = $app->get(RedisInterface::class);

        if (!$redis instanceof RedisInterface) {
            throw new \RuntimeException('The RedisInterface container binding is not a RedisInterface.');
        }

        return $redis;
    }
}
