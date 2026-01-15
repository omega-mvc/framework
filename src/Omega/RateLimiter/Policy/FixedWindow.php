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

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\RateLimiter\Policy;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;
use Omega\Cache\CacheInterface;
use Omega\RateLimiter\RateLimit;

use function floor;
use function max;
use function Omega\Time\now;

/**
 * Implements a fixed window rate limiting policy.
 *
 * This class enforces a maximum number of allowed attempts within a fixed time window.
 * Each key has its own counter in the current window. If the number of consumed
 * attempts exceeds the limit, the key is blocked until the next window starts.
 *
 * Example usage:
 * ```
 * $limiter = new FixedWindow($cache, 60, 60); // 60 attempts per 60 seconds
 * $rateLimit = $limiter->consume('user:123');
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
readonly class FixedWindow implements PolicyInterface
{
    /**
     * Create a new FixedWindow rate limiter.
     *
     * @param CacheInterface $cache The cache instance used to store counters
     * @param int $limit Maximum allowed attempts per window
     * @param int $windowSeconds Duration of the time window in seconds
     * @return void
     */
    public function __construct(
        private CacheInterface $cache,
        private int $limit,
        private int $windowSeconds,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function consume(string $key, int $token = 1): RateLimit
    {
        $windowKey = $this->getWindowKey($key);
        $consumed  = (int) $this->cache->get($windowKey, 0);

        if ($consumed + $token > $this->limit) {
            return new RateLimit(
                identifier: $key,
                limit: $this->limit,
                consumed: $consumed,
                remaining: max(0, $this->limit - $consumed),
                isBlocked: true,
                retryAfter: $this->getNextWindowStart(),
            );
        }

        $newConsumed = $this->cache->increment($windowKey, 1);
        if (1 === $newConsumed) {
            $this->cache->set($windowKey, 1, $this->windowSeconds);
        }

        return new RateLimit(
            identifier: $key,
            limit: $this->limit,
            consumed: $newConsumed,
            remaining: $this->limit - $newConsumed,
            isBlocked: false,
            retryAfter: $this->getNextWindowStart(),
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function peek(string $key): RateLimit
    {
        $windowKey = $this->getWindowKey($key);
        $consumed  = (int) $this->cache->get($windowKey, 0);

        return new RateLimit(
            identifier: $key,
            limit: $this->limit,
            consumed: $consumed,
            remaining: max(0, $this->limit - $consumed),
            isBlocked: $consumed >= $this->limit,
            retryAfter: $this->getNextWindowStart(),
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function reset(string $key): void
    {
        $this->cache->delete($this->getWindowKey($key));
    }

    /**
     * Generate the cache key for the current fixed window.
     *
     * @param string $key The unique identifier
     * @return string The computed window key used in the cache
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    private function getWindowKey(string $key): string
    {
        $windowStart = floor(now()->timestamp / $this->windowSeconds);

        return "{$key}:fw:{$windowStart}";
    }

    /**
     * Get the start DateTime of the next fixed window.
     *
     * @return DateTime The start time of the next window
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    private function getNextWindowStart(): DateTime
    {
        $currentWindow   = floor(now()->timestamp / $this->windowSeconds);
        $nextWindowStart = ($currentWindow + 1) * $this->windowSeconds;

        return new DateTime("@{$nextWindowStart}");
    }
}
