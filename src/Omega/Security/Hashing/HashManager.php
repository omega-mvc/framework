<?php

/**
 * Part of Omega - Security Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Security\Hashing;

use function array_key_exists;

/**
 * Manages multiple hashing drivers and delegates hash operations
 * to the appropriate implementation. Allows registering custom
 * drivers and defining a default hashing strategy.
 *
 * @category   Omega
 * @package    Security
 * @subpackage Hashing
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class HashManager implements HashInterface
{
    /** @var array<string, HashInterface> Registered hashing drivers. */
    private array $driver = [];

    /** @var HashInterface The default hashing driver. */
    private HashInterface $defaultDriver;

    /**
     * Initialize the manager with the default hashing driver.
     */
    public function __construct()
    {
        $this->setDefaultDriver(new DefaultHasher());
    }

    /**
     * Set the default hashing driver used when no specific driver
     * is requested.
     *
     * @param HashInterface $driver The driver to use as default.
     * @return self
     */
    public function setDefaultDriver(HashInterface $driver): self
    {
        $this->defaultDriver = $driver;
        return $this;
    }

    /**
     * Register a hashing driver under a given name.
     *
     * @param string $driverName A unique identifier for the driver.
     * @param HashInterface $driver The hashing driver instance.
     * @return self
     */
    public function setDriver(string $driverName, HashInterface $driver): self
    {
        $this->driver[$driverName] = $driver;
        return $this;
    }

    /**
     * Retrieve a hashing driver by name, or return the default
     * driver if none is found or the name is null.
     *
     * @param string|null $driver The name of the registered driver.
     * @return HashInterface The resolved hashing driver.
     */
    public function driver(?string $driver = null): HashInterface
    {
        if (array_key_exists($driver, $this->driver)) {
            return $this->driver[$driver];
        }

        return $this->defaultDriver;
    }

    /**
     * {@inheritdoc}
     */
    public function info(string $hash): array
    {
        return $this->driver()->info($hash);
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $value, array $options = []): string
    {
        return $this->driver()->make($value, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $value, string $hashedValue, array $options = []): bool
    {
        return $this->driver()->verify($value, $hashedValue, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function isValidAlgorithm(string $hash): bool
    {
        return $this->driver()->isValidAlgorithm($hash);
    }
}
