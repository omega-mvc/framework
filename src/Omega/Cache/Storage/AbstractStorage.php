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

namespace Omega\Cache\Storage;

use Closure;
use DateInterval;
use DateTimeInterface;
use Omega\Cache\CacheInterface;
use Omega\Cache\Exceptions\CacheConfigurationException;

/**
 * Class AbstractStorage
 *
 * Base abstract class providing partial implementation of the {@see CacheInterface}.
 * It defines common logic shared across cache storage drivers while leaving core
 * methods — such as `get`, `set`, `clear`, and `has` — to be implemented by
 * concrete storage classes (e.g., File, Memory, RedisStorage).
 *
 * This class also provides default implementations for common cache operations
 * like handling multiple keys, increment/decrement behavior, and the `remember`
 * pattern for lazy caching.
 *
 * @category   Omega
 * @package    Cache
 * @subpackage Storage
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
abstract class AbstractStorage implements CacheInterface, StorageInterface
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
     * {@inheritdoc}
     */
    abstract public function get(string $key, mixed $default = null): mixed;

    /**
     * {@inheritdoc}
     */
    abstract public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function clear(): bool;

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    abstract public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool;

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    abstract public function has(string $key): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function increment(string $key, int $value): int;

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value): int
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * {@inheritdoc}
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
     * Calculates a precise timestamp including microseconds based on the current time.
     *
     * This value is primarily used to track the creation or modification time
     * of cache items, allowing for more accurate expiration and profiling data.
     *
     * @return float Returns the calculated timestamp with millisecond precision.
     */
    public function createMtime(): float
    {
        $currentTime = time();
        $microtime   = microtime(true);

        $fractionalPart = $microtime - $currentTime;

        if ($fractionalPart >= 1) {
            $currentTime += (int) $fractionalPart;
            $fractionalPart -= (int) $fractionalPart;
        }

        $mtime = $currentTime + $fractionalPart;

        return round($mtime, 3);
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getInfo(string $key): array;

    /**
     * {@inheritdoc}
     */
    abstract public function calculateExpirationTimestamp(int|DateInterval|DateTimeInterface|null $ttl): int;
}
