<?php

declare(strict_types=1);

namespace Tests\Cache;

use Omega\Application\Application;
use Omega\Cache\CacheManager;
use Omega\Cache\CacheServiceProvider;
use Omega\Cache\Storage\ApcuStorage;
use Omega\Cache\Storage\FileStorage;
use Omega\Cache\Storage\MemcachedStorage;
use Omega\Cache\Storage\MemoryStorage;
use Omega\Cache\Storage\RedisStorage;
use Omega\Redis\RedisServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

#[CoversClass(CacheServiceProvider::class)]
final class CacheServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->app = new Application('/');
    }

    protected function tearDown(): void
    {
        $this->app->flush();
    }

    public function testBootsTheCacheManagerAndEveryAdapter(): void
    {
        $this->app->set('config', [
            'cache' => [
                'default' => 'file',
                'storage' => [
                    'file'      => ['ttl' => 3600, 'path' => __DIR__ . '/../fixtures/cache'],
                    'memory'    => ['ttl' => 3600],
                    'apcu'      => ['ttl' => 3600, 'prefix' => 'test_'],
                    'memcached' => ['ttl' => 3600, 'host' => '127.0.0.1', 'port' => 11211, 'prefix' => 'test_'],
                    'redis'     => ['ttl' => 3600, 'connection' => 'default'],
                ],
            ],
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
        (new CacheServiceProvider($this->app))->boot();

        $this->assertInstanceOf(CacheManager::class, $this->app->get('cache'));
        $this->assertInstanceOf(FileStorage::class, $this->app->get('cache.file'));
        $this->assertInstanceOf(MemoryStorage::class, $this->app->get('cache.memory'));
        $this->assertInstanceOf(ApcuStorage::class, $this->app->get('cache.apcu'));
        $this->assertInstanceOf(MemcachedStorage::class, $this->app->get('cache.memcached'));
        $this->assertInstanceOf(RedisStorage::class, $this->app->get('cache.redis'));
    }

    public function testThrowsForAnUnknownAdapter(): void
    {
        $this->app->set('config', [
            'cache' => [
                'default' => 'file',
                'storage' => [
                    'file'    => ['ttl' => 3600, 'path' => __DIR__ . '/../fixtures/cache'],
                    'unknown' => ['ttl' => 3600],
                ],
            ],
        ]);

        (new CacheServiceProvider($this->app))->boot();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown cache adapter: unknown');

        $this->app->get('cache.unknown');
    }
}
