<?php

/**
 * Part of Omega - Queue Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Queue;

use Closure;
use Omega\Queue\Exception\UnknownDriverException;
use Throwable;

use function array_keys;

class QueueManager
{
    /** @var array<string, QueueAdapterInterface|Closure(): QueueAdapterInterface> */
    private array $drivers = [];

    private QueueAdapterInterface $defaultDriver;

    private string $defaultDriverName;

    public function __construct(string $defaultDriverName, QueueAdapterInterface $defaultDriver)
    {
        $this->defaultDriverName = $defaultDriverName;
        $this->drivers[$defaultDriverName] = $defaultDriver;
        $this->defaultDriver = $defaultDriver;
    }

    public function getDefaultDriverName(): string
    {
        return $this->defaultDriverName;
    }

    /** @return list<string> */
    public function getDriverNames(): array
    {
        return array_keys($this->drivers);
    }

    public function setDriver(string $driverName, Closure|QueueAdapterInterface $driver): self
    {
        $this->drivers[$driverName] = $driver;

        return $this;
    }

    public function getDriver(?string $driverName = null): QueueAdapterInterface
    {
        if ($driverName === null) {
            return $this->defaultDriver;
        }

        if (!isset($this->drivers[$driverName])) {
            throw new UnknownDriverException($driverName);
        }

        return $this->resolve($driverName);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function push(Closure $job, array $data = [], ?string $queue = null, int $delay = 0): Job
    {
        return $this->getDriver()->push($job, $data, $queue ?? $this->defaultDriverName, $delay);
    }

    public function shift(?string $queue = null): ?Job
    {
        return $this->getDriver()->shift();
    }

    public function size(?string $queue = null): int
    {
        return $this->getDriver()->size($queue ?? $this->defaultDriverName);
    }

    public function delete(Job $job): bool
    {
        return $this->getDriver()->delete($job);
    }

    public function release(Job $job, int $delay = 0): bool
    {
        return $this->getDriver()->release($job, $delay);
    }

    public function failed(Job $job, Throwable $exception): void
    {
        $this->getDriver()->failed($job, $exception);
    }

    private function resolve(string $driverName): QueueAdapterInterface
    {
        $driver = $this->drivers[$driverName];

        if ($driver instanceof Closure) {
            $driver = $driver();
        }

        return $this->drivers[$driverName] = $driver;
    }
}
