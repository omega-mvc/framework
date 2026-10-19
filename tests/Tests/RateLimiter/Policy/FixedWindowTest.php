<?php

declare(strict_types=1);

namespace Tests\RateLimiter\Policy;

use Omega\Cache\Storage\MemoryStorage;
use Omega\RateLimiter\Policy\FixedWindow;
use Tests\TestCase;
use function floor;
use function Omega\Time\now;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class FixedWindowTest extends TestCase
{


    private MemoryStorage $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new MemoryStorage(['ttl' => 3600]);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();

        parent::tearDown();
    }

    public function testCanConsumeTokensWithinTheLimit(): void
    {
        $limiter   = new FixedWindow($this->cache, 5, 60);
        $rateLimit = $limiter->consume('test_key');

        $this->assertFalse($rateLimit->isBlocked());
        $this->assertSame(1, $rateLimit->getConsumed());
        $this->assertSame(4, $rateLimit->getRemaining());
    }

    public function testBlocksWhenConsumingTokensExceedsTheLimit(): void
    {
        $limiter = new FixedWindow($this->cache, 5, 60);

        for ($i = 0; $i < 5; $i++) {
            $limiter->consume('test_key');
        }

        $rateLimit = $limiter->consume('test_key');

        $this->assertTrue($rateLimit->isBlocked());
        $this->assertSame(5, $rateLimit->getConsumed());
        $this->assertSame(0, $rateLimit->getRemaining());
    }

    public function testCanPeekAtTheRateLimitStatus(): void
    {
        $limiter = new FixedWindow($this->cache, 5, 60);

        $this->cache->set('test_key:fw:' . floor(now()->getTimestamp() / 60), 3);

        $rateLimit = $limiter->peek('test_key');

        $this->assertFalse($rateLimit->isBlocked());
        $this->assertSame(3, $rateLimit->getConsumed());
        $this->assertSame(2, $rateLimit->getRemaining());
    }

    public function testCanResetTheRateLimit(): void
    {
        $limiter = new FixedWindow($this->cache, 5, 60);

        $limiter->consume('test_key');
        $limiter->reset('test_key');

        $rateLimit = $limiter->peek('test_key');

        $this->assertSame(0, $rateLimit->getConsumed());
    }

    public function testTreatsANonIntegerCachedValueAsZeroWhenConsuming(): void
    {
        $cache = new class(['ttl' => 3600]) extends MemoryStorage {
            private bool $returnString = false;

            public function returnString(bool $returnString): void
            {
                $this->returnString = $returnString;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                if ($this->returnString) {
                    return 'corrupted';
                }

                return parent::get($key, $default);
            }
        };

        $cache->returnString(true);

        $limiter   = new FixedWindow($cache, 5, 60);
        $rateLimit = $limiter->consume('test_key');

        $this->assertFalse($rateLimit->isBlocked());
        $this->assertSame(1, $rateLimit->getConsumed());
        $this->assertSame(4, $rateLimit->getRemaining());
    }

    public function testBlocksWhenTheRequestedTokensExceedTheLimit(): void
    {
        $limiter   = new FixedWindow($this->cache, 2, 60);
        $rateLimit = $limiter->consume('test_key', 3);

        $this->assertTrue($rateLimit->isBlocked());
        $this->assertSame(0, $rateLimit->getConsumed());
        $this->assertSame(2, $rateLimit->getRemaining());
    }

    public function testTreatsANonIntegerCachedValueAsZeroWhenPeeking(): void
    {
        $cache = new class(['ttl' => 3600]) extends MemoryStorage {
            private bool $returnString = false;

            public function returnString(bool $returnString): void
            {
                $this->returnString = $returnString;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                if ($this->returnString) {
                    return 'corrupted';
                }

                return parent::get($key, $default);
            }
        };

        $cache->returnString(true);

        $limiter   = new FixedWindow($cache, 5, 60);
        $rateLimit = $limiter->peek('test_key');

        $this->assertFalse($rateLimit->isBlocked());
        $this->assertSame(0, $rateLimit->getConsumed());
        $this->assertSame(5, $rateLimit->getRemaining());
    }

    public function testReportsTheKeyAsBlockedWhenTheLimitIsReached(): void
    {
        $limiter = new FixedWindow($this->cache, 5, 60);

        $this->cache->set('test_key:fw:' . floor(now()->getTimestamp() / 60), 5);

        $rateLimit = $limiter->peek('test_key');

        $this->assertTrue($rateLimit->isBlocked());
        $this->assertSame(5, $rateLimit->getConsumed());
        $this->assertSame(0, $rateLimit->getRemaining());
    }


}