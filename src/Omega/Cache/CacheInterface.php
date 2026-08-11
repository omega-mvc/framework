<?php

/**
 * Part of Omega - Cache Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cache;

use Closure;
use DateInterval;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

/**
 * Interface CacheInterface
 *
 * Defines a common contract for cache implementations within the Omega framework,
 * fully compatible with PSR-16 "Simple Cache" standard.
 *
 * This interface extends {@see PsrCacheInterface} and adds
 * additional convenience methods such as `increment`, `decrement`, and `remember`
 * to simplify common caching operations in applications.
 *
 * Implementations should be **safe**, **efficient**, and **driver-agnostic**,
 * supporting various backends like File, Memory, Redis, Memcached, or custom storages
 * through a unified API.
 *
 * Key features:
 * - Standard PSR-16 operations (get, set, delete, clear, etc.)
 * - Multi-key operations (getMultiple, setMultiple, deleteMultiple)
 * - Numeric increment/decrement operations
 * - Lazy value computation and storage (`remember`)
 * - Fully compatible with Omega's cache management system and `CacheFactory`
 *
 * @category  Omega
 * @package   Cache
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface CacheInterface extends PsrCacheInterface
{
    /**
     * Increment a numeric cache value.
     *
     * Increases the integer value stored under the given key by the specified amount.
     * If the key does not exist, it should be initialized to zero before incrementing.
     *
     * @param string $key The cache key.
     * @param int $value The amount to increment by.
     * @return int The new value after incrementing.
     */
    public function increment(string $key, int $value): int;

    /**
     * Decrement a numeric cache value.
     *
     * Decreases the integer value stored under the given key by the specified amount.
     * If the key does not exist, it should be initialized to zero before decrementing.
     *
     * @param string $key The cache key.
     * @param int $value The amount to decrement by.
     * @return int The new value after decrementing.
     */
    public function decrement(string $key, int $value): int;

    /**
     * Retrieve a cached value or compute and store it if missing.
     *
     * If the key does not exist, the callback will be executed and its return value
     * will be cached for the given TTL.
     *
     * @param string $key The unique cache key.
     * @param Closure $callback The callback to generate the value if not cached.
     * @param int|DateInterval|null $ttl Optional TTL for the cached value.
     * @return mixed The cached or newly computed value.
     */
    public function remember(string $key, Closure $callback, int|DateInterval|null $ttl): mixed;
}
