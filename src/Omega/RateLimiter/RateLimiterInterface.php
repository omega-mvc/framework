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

use DateInterval;

/**
 * Interface RateLimiterInterface
 *
 * Defines the contract for a rate limiter service.
 *
 * A rate limiter is responsible for tracking and controlling
 * the number of attempts associated with a given key
 * (e.g., user ID, IP address) over a specified decay period.
 *
 * Implementations of this interface allow:
 * - Checking if a key is blocked due to exceeding allowed attempts.
 * - Consuming attempts for failed actions.
 * - Retrieving the count of consumed attempts.
 * - Calculating the time remaining until a key can retry.
 * - Resetting attempts to unblock a key.
 *
 * This interface abstracts the rate-limiting logic,
 * enabling different storage mechanisms or policies
 * without affecting the consumer code.
 *
 * @category  Omega
 * @package   RateLimiter
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface RateLimiterInterface
{
    /**
     * Check if the given key is currently blocked due to exceeding the maximum attempts.
     *
     * @param string $key The key to check (e.g., user ID or IP address)
     * @param int $maxAttempts The maximum number of allowed attempts
     * @param int|DateInterval $decay The decay period in minutes or as a DateInterval
     * @return bool True if the key is blocked, false otherwise
     */
    public function isBlocked(string $key, int $maxAttempts, int|DateInterval $decay): bool;

    /**
     * Consume a single attempt for the given key.
     *
     * Each call represents a failed attempt that decreases the remaining allowance.
     *
     * @param string $key The key to consume an attempt for
     * @param int $decayMinutes The number of minutes the consumed attempt remains valid
     * @return int The number of attempts already consumed for this key
     */
    public function consume(string $key, int $decayMinutes = 1): int;

    /**
     * Get the number of consumed attempts for the given key.
     *
     * @param string $key The key to check
     * @return int The number of attempts already consumed
     */
    public function getCount(string $key): int;

    /**
     * Get the number of seconds until the key can retry.
     *
     * If the key is not blocked, returns 0.
     *
     * @param string $key The key to check
     * @return int Seconds until retry is allowed
     */
    public function getRetryAfter(string $key): int;

    /**
     * Get the number of remaining attempts for the given key.
     *
     * @param string $key The key to check
     * @param int $maxAttempts The maximum number of allowed attempts
     * @return int Remaining attempts before the key is blocked
     */
    public function remaining(string $key, int $maxAttempts): int;

    /**
     * Reset the consumed attempts for the given key.
     *
     * This effectively unblocks the key if it was blocked.
     *
     * @param string $key The key to reset
     * @return void
     */
    public function reset(string $key): void;
}
