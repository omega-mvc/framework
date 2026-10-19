<?php

declare(strict_types=1);

namespace Tests\RateLimiter\Policy;

use Omega\RateLimiter\Policy\NoLimiter;
use Tests\TestCase;
use const PHP_INT_MAX;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class NoLimiterTest extends TestCase
{


    public function testCanConsumeTokensWithinTheLimit(): void
    {
        $limiter   = new NoLimiter();
        $rateLimit = $limiter->consume('test_key');

        $this->assertFalse($rateLimit->isBlocked());
        $this->assertSame(0, $rateLimit->getConsumed());
        $this->assertSame(PHP_INT_MAX, $rateLimit->getRemaining());
    }

    public function testNeverBlocksWhenConsumingTokensExceedsTheLimit(): void
    {
        $limiter = new NoLimiter();

        for ($i = 0; $i < 5; $i++) {
            $rateLimit = $limiter->consume('test_key');

            $this->assertFalse($rateLimit->isBlocked());
            $this->assertSame(0, $rateLimit->getConsumed());
            $this->assertSame(PHP_INT_MAX, $rateLimit->getRemaining());
        }
    }

    public function testCanResetTheRateLimit(): void
    {
        $limiter   = new NoLimiter();
        $rateLimit = $limiter->consume('test_key');

        $this->assertFalse($rateLimit->isBlocked());

        $limiter->reset('test_key');

        $rateLimit = $limiter->peek('test_key');

        $this->assertSame(0, $rateLimit->getConsumed());
    }


}