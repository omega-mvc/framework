<?php

/**
 * Part of Omega - RateLimiter Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\RateLimiter;

use Omega\Cache\CacheInterface;
use Omega\RateLimiter\Policy\FixedWindow;
use Omega\RateLimiter\Policy\NoLimiter;

/**
 * Factory class for creating different types of rate limiters.
 *
 * This factory provides convenient methods to create instances of `RateLimiter`
 * with specific policies, such as a fixed-window limiter or a "no-op" limiter.
 *
 * Example usage:
 * ```
 * $factory = new RateLimiterFactory($cache);
 * $fixedLimiter = $factory->createFixedWindow(60, 60);
 * $noLimiter = $factory->createNoLimiter();
 * ```
 *
 * @category  Omega
 * @package   RateLimiter
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
 readonly class RateLimiterFactory
 {
    /**
     * Constructor.
     *
     * @param CacheInterface $cache The cache implementation used by rate limiters to store counters and window data
     * @return void
     */
    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * Create a fixed-window rate limiter.
     *
     * This limiter enforces a maximum number of attempts within a fixed time window.
     *
     * @param int $limit The maximum number of allowed attempts per window
     * @param int $windowSeconds The length of the time window in seconds
     * @return RateLimiterInterface A rate limiter instance enforcing the fixed-window policy
     */
    public function createFixedWindow(int $limit, int $windowSeconds): RateLimiterInterface
    {
        return new RateLimiter(
            new FixedWindow(
                cache: $this->cache,
                limit: $limit,
                windowSeconds: $windowSeconds,
            )
        );
    }

    /**
     * Create a "no-op" rate limiter.
     *
     * This limiter effectively disables rate limiting, always allowing requests
     * and reporting unlimited remaining attempts.
     *
     * @return RateLimiterInterface A rate limiter instance with no restriction
     */
    public function createNoLimiter(): RateLimiterInterface
    {
        return new RateLimiter(
            new NoLimiter()
        );
    }
 }
