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
     * Retrieve a value from the cache by its key.
     *
     * If the key does not exist or is expired, the default value will be returned.
     *
     * @param string $key The unique cache key to retrieve.
     * @param mixed|null $default The value to return if the key does not exist. Defaults to null.
     * @return mixed The cached value, or $default if the key does not exist.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in the cache under a unique key.
     *
     * An optional TTL (time-to-live) can be provided to control expiration time.
     *
     * @param string $key The unique cache key.
     * @param mixed $value The value to store in cache. Must be serializable.
     * @param int|DateInterval|null $ttl Optional. Time-to-live in seconds or a DateInterval. Null means no expiration.
     * @return bool True if the value was successfully stored, false otherwise.
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool;

    /**
     * Remove an item from the cache by its key.
     *
     * @param string $key The unique key identifying the cached item to remove.
     * @return bool True if the item was successfully deleted, false otherwise.
     */
    public function delete(string $key): bool;

    /**
     * Clear all items from the cache.
     *
     * This operation removes every key-value pair stored by the current cache driver.
     *
     * @return bool True on success, false on failure.
     */
    public function clear(): bool;

    /**
     * Retrieve multiple items from the cache at once.
     *
     * Each missing or expired key should return the provided default value.
     *
     * @param iterable<string> $keys A list of cache keys to retrieve.
     * @param mixed $default The default value for missing keys.
     * @return iterable<string, mixed> An associative list of key => value pairs.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable;

    /**
     * Store multiple key-value pairs in the cache at once.
     *
     * An optional TTL (time-to-live) may be provided for all items.
     *
     * @param iterable<string, mixed> $values A list of key => value pairs to store.
     * @param int|DateInterval|null $ttl Optional TTL for all keys. Null means no expiration.
     * @return bool True on success, false on failure.
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool;

    /**
     * Delete multiple cache items in a single operation.
     *
     * @param iterable<string> $keys A list of keys to remove from cache.
     * @return bool True if all provided keys were successfully deleted, false otherwise.
     */
    public function deleteMultiple(iterable $keys): bool;

    /**
     * Determine if a cache item exists.
     *
     * This method should not return expired items, and should be as fast as possible.
     *
     * @param string $key The cache key to check for existence.
     * @return bool True if the key exists and is not expired, false otherwise.
     */
    public function has(string $key): bool;

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
