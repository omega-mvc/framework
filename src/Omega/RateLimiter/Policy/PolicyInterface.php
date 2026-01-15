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

namespace Omega\RateLimiter\Policy;

use Omega\RateLimiter\RateLimit;

/**
 * Defines the contract for rate limiting policies.
 *
 * This interface abstracts the behavior of different rate limiting strategies
 * (e.g., fixed window, sliding window, token bucket). Implementations are
 * responsible for tracking attempts for a given key, determining if a key is
 * blocked, and resetting counters as necessary.
 *
 * Implementations must return a `RateLimit` object for both `consume` and `peek`,
 * providing details such as consumed attempts, remaining attempts, block status,
 * and retry time.
 *
 * Example usage:
 * ```
 * $limiterPolicy = new FixedWindow($cache, 60, 60); // 60 attempts per 60 seconds
 * $rateLimit = $limiterPolicy->consume('user:123');
 * if ($rateLimit->isBlocked()) { ... }
 * ```
 *
 * @category   Omega
 * @package    RateLimiter
 * @subpackage Policy
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
interface PolicyInterface
{
    /**
     * Consume a number of attempts for the given key.
     *
     * @param string $key The unique identifier for the user or resource
     * @param int $token Number of attempts to consume (default 1)
     * @return RateLimit The current rate limit status after consumption
     */
    public function consume(string $key, int $token = 1): RateLimit;

    /**
     * Peek the current rate limit status without consuming attempts.
     *
     * @param string $key The unique identifier for the user or resource
     * @return RateLimit The current rate limit status
     */
    public function peek(string $key): RateLimit;

    /**
     * Reset the consumed attempts for the given key.
     *
     * This should remove any tracking of attempts for the key,
     * effectively unblocking it if it was previously blocked.
     *
     * @param string $key The unique identifier for the user or resource
     * @return void
     */
    public function reset(string $key): void;
}
