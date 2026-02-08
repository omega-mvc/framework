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

namespace Tests\RateLimiter;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Cache\Storage\Memory;
use Omega\RateLimiter\Policy\FixedWindow;
use Omega\RateLimiter\RateLimiter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class RateLimiterTest
 *
 * This test suite verifies the core behavior of the `RateLimiter` wrapper, ensuring that
 * it correctly delegates rate limiting operations to the underlying strategy
 * implementation (`FixedWindow` in this case).
 *
 * The tests cover:
 * - Determining whether further actions are blocked (`isBlocked()`).
 * - Consuming allowance tokens (`consume()`).
 * - Retrieving the number of consumed actions (`getCount()`).
 * - Calculating retry-after timing (`getRetryAfter()`).
 * - Checking remaining allowed actions (`remaining()`).
 * - Resetting the limiter state for a given key (`reset()`).
 *
 * By validating these behaviors, this test suite ensures that the `RateLimiter` serves
 * as a reliable facade overrate limiting strategies while preserving consistent and
 * predictable API semantics.
 *
 * @category  Tests
 * @package   RateLimiter
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(FixedWindow::class)]
#[CoversClass(Memory::class)]
#[CoversClass(RateLimiter::class)]
final class RateLimiterTest extends TestCase
{
    /**
     * Rate limiter instance used throughout the test suite.
     *
     * This instance is configured with a `FixedWindow` rate limiting strategy backed by
     * in-memory storage (`Memory`). The limiter allows only one action per minute for the
     * test scenarios, enabling validation of consumption, blocking behavior, retry timing,
     * and state reset operations.
     *
     * @var RateLimiter
     */
    private RateLimiter $rateLimiter;

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
        $this->rateLimiter = new RateLimiter(
            new FixedWindow(
                cache: new Memory(['ttl' => 3600]),
                limit: 1,
                windowSeconds: 60
            )
        );
    }

    /**
     * Test is blocked.
     *
     * @return void
     */
    public function isBlocked(): void
    {
        $this->assertFalse($this->rateLimiter->isBlocked('key', 1, 1));
        $this->rateLimiter->consume('key');
        $this->assertTrue($this->rateLimiter->isBlocked('key', 1, 1));
    }

    /**
     * Test it consume.
     *
     * @return void
     */
    public function testItConsume(): void
    {
        $this->assertEquals(1, $this->rateLimiter->consume('key'));
    }

    /**
     * Test get count left.
     *
     * @return void
     */
    public function testGetCountLeft(): void
    {
        $this->assertEquals(0, $this->rateLimiter->getCount('key'));
        $this->rateLimiter->consume('key');
        $this->assertEquals(1, $this->rateLimiter->getCount('key'));
    }

    /**
     * Test get retry after.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function getRetryAfter(): void
    {
        $this->assertGreaterThan(0, $this->rateLimiter->getRetryAfter('key'));
    }

    /**
     * Test it remaining.
     *
     * @return void
     */
    public function testItRemaining(): void
    {
        $this->assertEquals(1, $this->rateLimiter->remaining('key', 1));
        $this->rateLimiter->consume('key');
        $this->assertEquals(0, $this->rateLimiter->remaining('key', 1));
    }

    /**
     * Test it reset.
     *
     * @return void
     */
    public function testItReset(): void
    {
        $this->rateLimiter->consume('key');
        $this->rateLimiter->reset('key');
        $this->assertEquals(0, $this->rateLimiter->getCount('key'));
    }
}
