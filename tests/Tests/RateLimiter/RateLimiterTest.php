<?php

declare(strict_types=1);

namespace Tests\RateLimiter;

use Omega\Cache\Storage\MemoryStorage;
use Omega\RateLimiter\Policy\FixedWindow;
use Omega\RateLimiter\Policy\NoLimiter;
use Omega\RateLimiter\RateLimiter;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(FixedWindow::class)]
#[CoversClass(MemoryStorage::class)]
#[CoversClass(NoLimiter::class)]
#[CoversClass(RateLimiter::class)]
final class RateLimiterTest extends TestCase
{
    private RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rateLimiter = new RateLimiter(
            new FixedWindow(
                cache: new MemoryStorage(['ttl' => 3600]),
                limit: 1,
                windowSeconds: 60
            )
        );
    }

    public function testConsume(): void
    {
        $this->assertSame(1, $this->rateLimiter->consume('key'));
    }

    public function testGetCountLeft(): void
    {
        $this->assertSame(0, $this->rateLimiter->getCount('key'));

        $this->rateLimiter->consume('key');

        $this->assertSame(1, $this->rateLimiter->getCount('key'));
    }

    public function testRemaining(): void
    {
        $this->assertSame(1, $this->rateLimiter->remaining('key', 1));

        $this->rateLimiter->consume('key');

        $this->assertSame(0, $this->rateLimiter->remaining('key', 1));
    }

    public function testReset(): void
    {
        $this->rateLimiter->consume('key');
        $this->rateLimiter->reset('key');

        $this->assertSame(0, $this->rateLimiter->getCount('key'));
    }

    public function testIsBlocked(): void
    {
        $this->assertFalse($this->rateLimiter->isBlocked('key', 1, 1));

        $this->rateLimiter->consume('key');

        $this->assertTrue($this->rateLimiter->isBlocked('key', 1, 1));
    }

    public function testGetRetryAfterWhenNotBlocked(): void
    {
        $rateLimiter = new RateLimiter(new NoLimiter());

        $this->assertSame(0, $rateLimiter->getRetryAfter('key'));
    }

    public function testGetRetryAfterWhenBlocked(): void
    {
        $rateLimiter = new RateLimiter(
            new FixedWindow(
                cache: new MemoryStorage(['ttl' => 3600]),
                limit: 1,
                windowSeconds: 3600
            )
        );

        $rateLimiter->consume('key');

        $retryAfter = $rateLimiter->getRetryAfter('key');

        $this->assertGreaterThanOrEqual(0, $retryAfter);
        $this->assertLessThanOrEqual(3600, $retryAfter);
    }
}
