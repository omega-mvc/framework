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

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Cache\Storage\Memory;
use Omega\RateLimiter\Policy\FixedWindow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function floor;
use function Omega\Time\now;

/**
 * Class FixedWindowTest
 *
 * This test suite verifies the behavior of the fixed-window rate limiter implementation.
 * The FixedWindow limiter allows a defined number of operations (the limit) to occur within
 * a fixed time window. Once the limit is reached, further requests are blocked until the
 * window resets.
 *
 * The tests cover:
 * - Consuming tokens while remaining under the limit.
 * - Blocking additional requests once the limit is exceeded.
 * - Inspecting the current rate limit state without modifying it (`peek()`).
 * - Resetting the usage count for a given key.
 *
 * An in-memory storage (`Memory`) is used to keep the tests fast and deterministic,
 * avoiding external dependencies or persistent state.
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
#[CoversClass(FixedWindow::class)]
#[CoversClass(Memory::class)]
class FixedWindowTest extends TestCase
{
    /**
     * In-memory storage used by the rate limiter during testing.
     *
     * The Memory driver simulates cache behavior without writing to disk or external services,
     * ensuring that rate limit operations remain isolated, fast, and easily resettable between tests.
     *
     * @var Memory
     */
    private Memory $cache;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new Memory(['ttl' => 3600]);
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cache->clear();
    }

    /**
     * Test it can consume tokens within the limit.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanConsumeTokensWithinTheLimit(): void
    {
        $limiter   = new FixedWindow($this->cache, 5, 60);
        $rateLimit = $limiter->consume('test_key');

        $this->assertFalse($rateLimit->isBlocked());
        $this->assertEquals(1, $rateLimit->getConsumed());
        $this->assertEquals(4, $rateLimit->getRemaining());
    }

    /**
     * Test it blocks when consuming tokens exceeds the limit.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItBlocksWhenConsumingTokensExceedsTheLimit(): void
    {
        $limiter = new FixedWindow($this->cache, 5, 60);

        for ($i = 0; $i < 5; $i++) {
            $limiter->consume('test_key');
        }

        $rateLimit = $limiter->consume('test_key');

        $this->assertTrue($rateLimit->isBlocked());
        $this->assertEquals(5, $rateLimit->getConsumed());
        $this->assertEquals(0, $rateLimit->getRemaining());
    }

    /**
     * Test it can peek at the rate limit status.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanPeekAtTheRateLimitStatus(): void
    {
        $limiter = new FixedWindow($this->cache, 5, 60);

        $this->cache->set('test_key:fw:' .  floor(now()->timestamp / 60), 3);

        $rateLimit = $limiter->peek('test_key');

        $this->assertFalse($rateLimit->isBlocked());
        $this->assertEquals(3, $rateLimit->getConsumed());
        $this->assertEquals(2, $rateLimit->getRemaining());
    }

    /**
     * Test it can reset the rate limit.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanResetTheRateLimit(): void
    {
        $limiter = new FixedWindow($this->cache, 5, 60);

        $limiter->consume('test_key');
        $limiter->reset('test_key');

        $rateLimit = $limiter->peek('test_key');

        $this->assertEquals(0, $rateLimit->getConsumed());
    }
}
