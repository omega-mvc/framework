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
use Omega\Cache\Exceptions\UnknownStorageException;

use function is_callable;

/**
 * Class CacheManager
 *
 * The CacheManager acts as a central point of access for all cache storage drivers.
 * It allows setting and retrieving multiple cache drivers (e.g. file, memory, Redis),
 * and automatically delegates cache operations to the default driver if no specific
 * driver is requested.
 *
 * @category  Omega
 * @package   Cache
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class CacheFactory implements CacheInterface
{
    /**
     * Registered cache drivers.
     *
     * Each driver can be a direct instance of {@see CacheInterface} or a lazy-loaded
     * closure returning a cache instance.
     *
     * @var array<string, CacheInterface|Closure(): CacheInterface>
     */
    private array $driver = [];

    /** @var CacheInterface The default cache driver used when no specific driver name is provided. */
    private CacheInterface $defaultDriver;

    /**
     * Initializes a new CacheFactory instance with the specified default cache driver.
     *
     * This constructor registers the provided cache driver as the default driver
     * and ensures that all subsequent cache operations (via getDriver() or
     * magic methods) will use this driver unless explicitly overridden.
     *
     * @param string         $defaultDriverName The name of the default cache driver.
     * @param CacheInterface $defaultDriver     The cache driver instance to use as default.
     */
    public function __construct(string $defaultDriverName, CacheInterface $defaultDriver)
    {
        $this->driver[$defaultDriverName] = $defaultDriver;
        $this->defaultDriver = $defaultDriver;
    }

    /**
     * Sets the default cache driver instance.
     *
     * @param CacheInterface $driver The cache driver to be used as default.
     * @return $this Returns the current instance for method chaining.
     */
    public function setDefaultDriver(CacheInterface $driver): self
    {
        $this->defaultDriver = $driver;

        return $this;
    }

    /**
     * Registers a named cache driver.
     *
     * Drivers can be added either as ready-to-use instances or as closures
     * that return a {@see CacheInterface} instance upon resolution.
     *
     * @param string                                   $driverName The unique driver name.
     * @param Closure(): CacheInterface|CacheInterface $driver     The driver instance or a closure returning it.
     * @return self Returns the current instance for method chaining.
     */
    public function setDriver(string $driverName, Closure|CacheInterface $driver): self
    {
        $this->driver[$driverName] = $driver;

        return $this;
    }

    /**
     * Resolves a cache driver by its registered name.
     *
     * If the driver is registered as a closure, it will be executed and its
     * resulting instance cached for future use.
     *
     * @param string $driverName The name of the driver to resolve.
     * @return CacheInterface The resolved cache driver instance.
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    private function resolve(string $driverName): CacheInterface
    {
        $driver = $this->driver[$driverName];

        if (is_callable($driver)) {
            $driver = $driver();
        }

        if (null === $driver) {
            throw new UnknownStorageException($driverName);
        }

        return $this->driver[$driverName] = $driver;
    }

    /**
     * Retrieves a cache driver by name or returns the default driver if none is provided.
     *
     * @param string|null $driverName Optional name of the driver to use.
     * @return CacheInterface The corresponding cache driver instance.
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function getDriver(?string $driverName = null): CacheInterface
    {
        if (isset($this->driver[$driverName])) {
            return $this->resolve($driverName);
        }

        return $this->defaultDriver;
    }

    /**
     * Magic method to delegate cache operations to the default driver.
     *
     * This allows direct method calls (e.g., `$cache->get('key')`) on the manager
     * without explicitly calling `driver()`.
     *
     * @param string $method The method name being called.
     * @param array  $parameters The parameters passed to the method.
     * @return mixed The result returned by the underlying cache driver.
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->getDriver()->{$method}(...$parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getDriver()->get($key, $default);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        return $this->getDriver()->set($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function delete(string $key): bool
    {
        return $this->getDriver()->delete($key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function clear(): bool
    {
        return $this->getDriver()->clear();
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->getDriver()->getMultiple($keys, $default);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        return $this->getDriver()->setMultiple($values, $ttl);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return $this->getDriver()->deleteMultiple($keys);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function has(string $key): bool
    {
        return $this->getDriver()->has($key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function increment(string $key, int $value): int
    {
        return $this->getDriver()->increment($key, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function decrement(string $key, int $value): int
    {
        return $this->getDriver()->decrement($key, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function remember(string $key, Closure $callback, int|DateInterval|null $ttl): mixed
    {
        return $this->getDriver()->remember($key, $callback, $ttl);
    }
}
