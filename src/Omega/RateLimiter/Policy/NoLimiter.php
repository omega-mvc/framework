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

use const PHP_INT_MAX;

/**
 * A "no-op" rate limiter implementation that effectively disables rate limiting.
 *
 * This implementation of `PolicyInterface` never blocks any key and
 * always reports unlimited attempts. It is useful for development, testing, or
 * scenarios where rate limiting is not required.
 *
 * All methods return a `RateLimit` instance with `isBlocked` set to `false`,
 * `consumed` set to 0, and `remaining` set to `PHP_INT_MAX`.
 *
 * Example usage:
 * ```
 * $noLimiter = new NoLimiter();
 * $rateLimit = $noLimiter->consume('user:123');
 * echo $rateLimit->getRemaining(); // Outputs PHP_INT_MAX
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
class NoLimiter implements PolicyInterface
{
    /**
     * {@inheritdoc}
     */
    public function consume(string $key, int $token = 1): RateLimit
    {
        return new RateLimit(
            identifier: $key,
            limit: PHP_INT_MAX,
            consumed: 0,
            remaining: PHP_INT_MAX,
            isBlocked: false
        );
    }

    /**
     * {@inheritdoc}
     */
    public function peek(string $key): RateLimit
    {
        return new RateLimit(
            identifier: $key,
            limit: PHP_INT_MAX,
            consumed: 0,
            remaining: PHP_INT_MAX,
            isBlocked: false
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $key): void
    {
        // No operation for NoLimiter
    }
}
