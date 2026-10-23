<?php

declare(strict_types=1);

namespace Tests\RateLimiter;

use DateTime;
use Omega\RateLimiter\RateLimit;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class RateLimitTest extends TestCase
{
    public function testExposesTheRateLimitStateWithRetryAndExpirationDates(): void
    {
        $retryAfter = new DateTime('@1000');
        $expiresAt  = new DateTime('@2000');

        $rateLimit = new RateLimit(
            identifier: 'user:1',
            limit: 5,
            consumed: 2,
            remaining: 3,
            isBlocked: true,
            retryAfter: $retryAfter,
            expiresAt: $expiresAt,
        );

        $this->assertSame('user:1', $rateLimit->getIdentifier());
        $this->assertSame(5, $rateLimit->getLimit());
        $this->assertSame(2, $rateLimit->getConsumed());
        $this->assertSame(3, $rateLimit->getRemaining());
        $this->assertTrue($rateLimit->isBlocked());
        $this->assertSame($retryAfter, $rateLimit->getRetryAfter());
        $this->assertSame($expiresAt, $rateLimit->getExpiresAt());
    }

    public function testDefaultsRetryAndExpirationDatesToNull(): void
    {
        $rateLimit = new RateLimit(
            identifier: 'user:1',
            limit: 5,
            consumed: 0,
            remaining: 5,
            isBlocked: false,
        );

        $this->assertSame('user:1', $rateLimit->getIdentifier());
        $this->assertSame(5, $rateLimit->getLimit());
        $this->assertSame(0, $rateLimit->getConsumed());
        $this->assertSame(5, $rateLimit->getRemaining());
        $this->assertFalse($rateLimit->isBlocked());
        $this->assertNull($rateLimit->getRetryAfter());
        $this->assertNull($rateLimit->getExpiresAt());
    }
}
