<?php

declare(strict_types=1);

namespace Tests\RateLimiter;

use Omega\Cache\Storage\MemoryStorage;
use Omega\RateLimiter\RateLimiter;
use Omega\RateLimiter\RateLimiterFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(MemoryStorage::class)]
#[CoversClass(RateLimiterFactory::class)]
final class RateLimiterFactoryTest extends TestCase
{
    public function testCanCreateRateLimiter(): void
    {
        $factory = new RateLimiterFactory(new MemoryStorage(['ttl' => 3600]));

        $this->assertInstanceOf(RateLimiter::class, $factory->createFixedWindow(10, 60));
        $this->assertInstanceOf(RateLimiter::class, $factory->createNoLimiter());
    }
}
