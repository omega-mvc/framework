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
use Omega\Cache\Exceptions\CacheConfigurationException;
use Omega\Cache\Storage\StorageInterface;

/**
 * Class AbstractCache
 *
 * Provides a base implementation for cache storage systems.
 *
 * This abstract class defines common behaviors and utilities for
 * cache drivers, including support for default TTL (time-to-live),
 * batch operations, and value increment/decrement handling.
 *
 * Concrete cache implementations (e.g., File, Memory, Redis, APCu)
 * should extend this class and implement the low-level storage operations
 * required by {@see CacheInterface} and {@see StorageInterface}.
 *
 * Features provided by this class include:
 * - Default TTL management for cache items.
 * - Batch retrieval and deletion of multiple keys.
 * - Numeric increment and decrement operations.
 * - Lazy value computation and caching via the `remember` method.
 * - Basic key metadata via `getInfo` (to be extended by specific drivers).
 *
 * @category  Omega
 * @package   Cache
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
abstract class AbstractCache implements CacheInterface, StorageInterface
{
    /** @var int|DateInterval The default time-to-live (TTL) in seconds for cache items. */
    protected int|DateInterval $defaultTTL;

    /**
     * AbstractStorage constructor.
     *
     * Initializes the storage with required options.
     *
     * Required keys in $options:
     * - 'ttl' : int|DateInterval  The default time-to-live for cache items.
     *
     * @param array{
     *   ttl: int|DateInterval,
     *   path: string,
     * } $options Configuration options for the storage.
     * @return void
     * @throws CacheConfigurationException If the 'ttl' option is missing.
     */
    public function __construct(array $options)
    {
        if (!isset($options['ttl'])) {
            throw new CacheConfigurationException('The TTL (time-to-live) option is required.');
        }

        $this->defaultTTL = $options['ttl'];
    }

    /**
     * Retrieve multiple items from the cache at once.
     *
     * Each missing or expired key should return the provided default value.
     *
     * @param iterable<string> $keys A list of cache keys to retrieve.
     * @param mixed $default The default value for missing keys.
     * @return iterable<string, mixed> An associative list of key => value pairs.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * Delete multiple cache items in a single operation.
     *
     * @param iterable<string> $keys A list of keys to remove from cache.
     * @return bool True if all provided keys were successfully deleted, false otherwise.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $state = null;

        foreach ($keys as $key) {
            $result = $this->delete($key);

            $state = null === $state ? $result : $result && $state;
        }

        return $state ?: false;
    }

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
    public function decrement(string $key, int $value): int
    {
        return $this->increment($key, $value * -1);
    }

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
    public function remember(string $key, Closure $callback, int|DateInterval|null $ttl): mixed
    {
        $value = $this->get($key);

        if (null !== $value) {
            return $value;
        }

        $this->set($key, $value = $callback(), $ttl);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(string $key): array
    {
        return [];
    }
}
