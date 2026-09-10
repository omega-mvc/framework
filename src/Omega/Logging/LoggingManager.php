<?php

/**
 * Part of Omega - Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Logging;

use Closure;
use Omega\Logging\Exception\UnknownDriverException;
use Psr\Log\LoggerInterface;
use Stringable;

use function is_callable;

/**
 * Class LoggingManager.
 *
 * The LoggingManager acts as a central point of access for all log drivers.
 * It allows setting and retrieving multiple log drivers (e.g. stream, syslog,
 * custom), and automatically delegates logging operations to the default
 * driver if no specific driver is requested.
 *
 * @category  Omega
 * @package   Logging
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class LoggingManager implements LoggerInterface
{
    /**
     * Registered log drivers.
     *
     * Each driver can be a direct instance of {@see LoggerInterface} or a lazy-loaded
     * closure returning a logger instance.
     *
     * @var array<string, LoggerInterface|Closure(): LoggerInterface>
     */
    private array $driver = [];

    /** @var LoggerInterface The default log driver used when no specific driver name is provided. */
    private LoggerInterface $defaultDriver;

    /**
     * Initializes a new LoggingManager instance with the specified default log driver.
     *
     * This constructor registers the provided log driver as the default driver
     * and ensures that all subsequent logging operations (via getDriver() or
     * magic methods) will use this driver unless explicitly overridden.
     *
     * @param string          $defaultDriverName The name of the default log driver.
     * @param LoggerInterface $defaultDriver     The log driver instance to use as default.
     */
    public function __construct(string $defaultDriverName, LoggerInterface $defaultDriver)
    {
        $this->driver[$defaultDriverName] = $defaultDriver;
        $this->defaultDriver = $defaultDriver;
    }

    /**
     * Sets the default log driver instance.
     *
     * @param LoggerInterface $driver The log driver to be used as default.
     * @return self Returns the current instance for method chaining.
     */
    public function setDefaultDriver(LoggerInterface $driver): self
    {
        $this->defaultDriver = $driver;

        return $this;
    }

    /**
     * Registers a named log driver.
     *
     * Drivers can be added either as ready-to-use instances or as closures
     * that return a {@see LoggerInterface} instance upon resolution.
     *
     * @param string                                 $driverName The unique driver name.
     * @param Closure(): LoggerInterface|LoggerInterface $driver  The driver instance or a closure returning it.
     * @return self Returns the current instance for method chaining.
     */
    public function setDriver(string $driverName, Closure|LoggerInterface $driver): self
    {
        $this->driver[$driverName] = $driver;

        return $this;
    }

    /**
     * Resolves a log driver by its registered name.
     *
     * If the driver is registered as a closure, it will be executed and its
     * resulting instance cached for future use.
     *
     * @param string $driverName The name of the driver to resolve.
     * @return LoggerInterface The resolved log driver instance.
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    private function resolve(string $driverName): LoggerInterface
    {
        $driver = $this->driver[$driverName];

        if (is_callable($driver)) {
            $driver = $driver();
        }

        if (null === $driver) {
            throw new UnknownDriverException($driverName);
        }

        return $this->driver[$driverName] = $driver;
    }

    /**
     * Retrieves a log driver by name or returns the default driver if none is provided.
     *
     * @param string|null $driverName Optional name of the driver to use.
     * @return LoggerInterface The corresponding log driver instance.
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function getDriver(?string $driverName = null): LoggerInterface
    {
        if (null === $driverName) {
            return $this->defaultDriver;
        }

        if (!isset($this->driver[$driverName])) {
            throw new UnknownDriverException($driverName);
        }

        return $this->resolve($driverName);
    }

    /**
     * Magic method to delegate logging operations to the default driver.
     *
     * This allows direct method calls (e.g., `$logger->write('message')`) on
     * the manager without explicitly calling `getDriver()`.
     *
     * @param string $method     The method name being called.
     * @param array  $parameters The parameters passed to the method.
     * @return mixed The result returned by the underlying log driver.
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->getDriver()->{$method}(...$parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->getDriver()->emergency($message, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->getDriver()->alert($message, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->getDriver()->critical($message, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->getDriver()->error($message, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->getDriver()->warning($message, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->getDriver()->notice($message, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->getDriver()->info($message, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->getDriver()->debug($message, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnknownDriverException if a requested log driver is unknown, unregistered, or unsupported.
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->getDriver()->log($level, $message, $context);
    }
}
