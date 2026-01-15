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

use DateTime;

/**
 * Represents the current state of a rate limit for a specific identifier.
 *
 * This class encapsulates information about the limit, the number of
 * attempts consumed, the remaining attempts, and whether the identifier
 * is currently blocked. It also provides optional timestamps for when
 * the key can retry and when the rate limit expires.
 *
 * @category  Omega
 * @package   RateLimiter
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final readonly class RateLimit
{
    /**
     * Create a new RateLimit instance.
     *
     * @param string $identifier The unique identifier for the rate limit (e.g., user ID, IP address)
     * @param int $limit The maximum number of allowed attempts
     * @param int $consumed The number of attempts already consumed
     * @param int $remaining The number of remaining attempts
     * @param bool $isBlocked Whether the identifier is currently blocked
     * @param DateTime|null $retryAfter Optional DateTime when the key can retry
     * @param DateTime|null $expiresAt Optional DateTime when the rate limit expires
     */
    public function __construct(
        private string $identifier,
        private int $limit,
        private int $consumed,
        private int $remaining,
        private bool $isBlocked,
        private ?DateTime $retryAfter = null,
        private ?DateTime $expiresAt = null,
    ) {
    }

    /**
     * Get the unique identifier for this rate limit.
     *
     * @return string The identifier (e.g., user ID, IP address)
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get the maximum number of allowed attempts.
     *
     * @return int The limit
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Get the number of attempts that have already been consumed.
     *
     * @return int Number of consumed attempts
     */
    public function getConsumed(): int
    {
        return $this->consumed;
    }

    /**
     * Get the number of remaining attempts before the identifier is blocked.
     *
     * @return int Remaining attempts
     */
    public function getRemaining(): int
    {
        return $this->remaining;
    }

    /**
     * Determine if the identifier is currently blocked.
     *
     * @return bool True if blocked, false otherwise
     */
    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    /**
     * Get the DateTime when the identifier can retry.
     *
     * Returns null if the key is not blocked or if no retry time is set.
     *
     * @return DateTime|null Retry timestamp or null
     */
    public function getRetryAfter(): ?DateTime
    {
        return $this->retryAfter;
    }

    /**
     * Get the DateTime when this rate limit expires.
     *
     * Returns null if no expiration is defined.
     *
     * @return DateTime|null Expiration timestamp or null
     */
    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }
}
