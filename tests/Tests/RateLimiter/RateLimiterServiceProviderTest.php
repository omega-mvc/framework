<?php

declare(strict_types=1);

namespace Tests\RateLimiter;

use Omega\Application\Application;
use Omega\Cache\CacheManager;
use Omega\Cache\Storage\MemoryStorage;
use Omega\Middleware\ThrottleMiddleware;
use Omega\RateLimiter\RateLimiterFactory;
use Omega\RateLimiter\RateLimiterServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(CacheManager::class)]
#[CoversClass(MemoryStorage::class)]
#[CoversClass(RateLimiterFactory::class)]
#[CoversClass(RateLimiterServiceProvider::class)]
#[CoversClass(ThrottleMiddleware::class)]
final class RateLimiterServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application('/');

        $this->app->set('cache', new CacheManager(
            'array',
            new MemoryStorage(['ttl' => 3600])
        ));
    }

    protected function tearDown(): void
    {
        $this->app->flush();

        parent::tearDown();
    }

    public function testBootsAndResolvesTheRateLimiterFactory(): void
    {
        (new RateLimiterServiceProvider($this->app))->boot();

        $factory = $this->app->get(RateLimiterFactory::class);

        $this->assertInstanceOf(RateLimiterFactory::class, $factory);
        $this->assertSame($factory, $this->app->get(RateLimiterFactory::class));
    }

    public function testBootsAndResolvesTheThrottleMiddleware(): void
    {
        (new RateLimiterServiceProvider($this->app))->boot();

        $middleware = $this->app->get(ThrottleMiddleware::class);

        $this->assertInstanceOf(ThrottleMiddleware::class, $middleware);
        $this->assertSame($middleware, $this->app->get(ThrottleMiddleware::class));
    }
}
