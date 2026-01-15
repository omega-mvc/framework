<?php

/**
 * Part of Omega - Tests\RateLimiter Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\RateLimiter\Policy;

use Omega\RateLimiter\Policy\NoLimiter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const PHP_INT_MAX;

/**
 * Class NoLimiterTest
 *
 * This test suite verifies the behavior of the `NoLimiter` rate limiter implementation.
 * Unlike traditional limiters, `NoLimiter` applies no actual restriction on the number
 * of allowed operations. Every request is always permitted, and the usage count remains
 * effectively zero, while the remaining allowance is represented as `PHP_INT_MAX`.
 *
 * The tests ensure that:
 * - Calls to `consume()` never block the caller.
 * - Usage does not increase regardless of how many times `consume()` is invoked.
 * - `reset()` and `peek()` operate consistently, even though no real state is tracked.
 *
 * This limiter is mainly useful in contexts where rate limiting behavior is optional,
 * disabled, or managed externally.
 *
 * @category   Tests
 * @package    RateLimiter
 * @subpackage Policy
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(NoLimiter::class)]
class NoLimiterTest extends TestCase
{
    /**
     * Test it can consume tokens within the limit.
     *
     * @return void
     */
    public function testItCanConsumeTokensWithinTheLimit(): void
    {
        $limiter   = new NoLimiter();
        $rateLimit = $limiter->consume('test_key');

        $this->assertFalse($rateLimit->isBlocked());
        $this->assertEquals(0, $rateLimit->getConsumed());
        $this->assertEquals(PHP_INT_MAX, $rateLimit->getRemaining());
    }

    /**
     * Test it never blocks when consuming tokens exceeds the limit.
     *
     * @return void
     */
    public function testItNeverBlocksWhenConsumingTokensExceedsTheLimit(): void
    {
        $limiter = new NoLimiter();

        for ($i = 0; $i < 5; $i++) {
            $limiter->consume('test_key');
        }

        $rateLimit = $limiter->consume('test_key');

        $this->assertFalse($rateLimit->isBlocked());
        $this->assertEquals(0, $rateLimit->getConsumed());
        $this->assertEquals(PHP_INT_MAX, $rateLimit->getRemaining());
    }

    /**
     * Test it can reset the rate limiter.
     *
     * @return void
     */
    public function testItCanResetTheRateLimit(): void
    {
        $limiter = new NoLimiter();

        $limiter->consume('test_key');
        $limiter->reset('test_key');

        $rateLimit = $limiter->peek('test_key');

        $this->assertEquals(0, $rateLimit->getConsumed());
    }
}
