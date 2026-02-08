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

use Omega\Cache\Storage\Memory;
use Omega\RateLimiter\RateLimiterFactory;
use Omega\RateLimiter\RateLimiterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class RateLimiterFactoryTest
 *
 * This test suite validates the behavior of the `RateLimiterFactory`, ensuring that it
 * correctly instantiates different rate limiter implementations based on the requested
 * strategy.
 *
 * The factory is initialized with a cache backend (in this case `Memory`), which is used
 * by limiters such as `FixedWindow` to track consumption state. The tests verify that:
 *
 * - `createFixedWindow()` returns a valid implementation of `RateLimiterInterface`
 *   configured with a maximum number of allowed actions and a time window.
 * - `createNoLimiter()` returns a rate limiter that imposes no restrictions, also
 *   implementing `RateLimiterInterface`.
 *
 * This guarantees that the factory abstraction remains consistent and that consumers
 * can rely on it to provide the appropriate limiter for runtime needs.
 *
 * @category  Tests
 * @package   RateLimiter
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Memory::class)]
#[CoversClass(RateLimiterFactory::class)]
final class RateLimiterFactoryTest extends TestCase
{
    /**
     * Test it can create rate limiter.
     *
     * @return void
     */
    public function testItCanCreateRateLimiter(): void
    {
        $factory = new RateLimiterFactory(new Memory(['ttl' => 3600]));

        $this->assertInstanceOf(
            RateLimiterInterface::class,
            $factory->createFixedWindow(10, 60)
        );

        $this->assertInstanceOf(
            RateLimiterInterface::class,
            $factory->createNoLimiter()
        );
    }
}
