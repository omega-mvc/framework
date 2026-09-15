<?php

/**
 * Part of Omega - Tests\Queue Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Queue\Support;

use Closure;
use Omega\Queue\Job;
use Omega\Queue\QueueAdapterInterface;
use Omega\SerializableClosure\UnsignedSerializableClosure;
use PHPUnit\Framework\Attributes\CoversNothing;
use Throwable;

use function array_shift;
use function count;
use function serialize;
use function time;

/**
 * In-memory queue adapter that records every call made by the QueueManager.
 *
 * @category  Tests
 * @package   Queue
 * @subpackage Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversNothing]
final class FakeQueueAdapter implements QueueAdapterInterface
{
    /** @var list<Job> */
    private array $jobs = [];

    private int $nextId = 1;

    /** @var list<array{job: Closure, data: array<string, mixed>, queue: string, delay: int}> */
    private array $pushed = [];

    /** @var list<string> */
    private array $sizeQueues = [];

    /** @var list<Job> */
    private array $deleted = [];

    /** @var list<Job> */
    private array $released = [];

    /** @var list<Job> */
    private array $failed = [];

    /**
     * @param array<string, mixed> $data
     */
    public function push(Closure $job, array $data = [], string $queue = 'default', int $delay = 0): Job
    {
        $jobInstance = new Job(
            id: $this->nextId++,
            queue: $queue,
            payload: serialize(new UnsignedSerializableClosure($job)),
            attempts: 0,
            reservedAt: null,
            availableAt: null,
            createdAt: (string) time(),
        );

        $this->jobs[] = $jobInstance;
        $this->pushed[] = ['job' => $job, 'data' => $data, 'queue' => $queue, 'delay' => $delay];

        return $jobInstance;
    }

    public function shift(): ?Job
    {
        return array_shift($this->jobs);
    }

    public function size(string $queue = 'default'): int
    {
        $this->sizeQueues[] = $queue;

        return count($this->jobs);
    }

    public function delete(Job $job): bool
    {
        $this->deleted[] = $job;
        $job->markDeleted();

        return true;
    }

    public function release(Job $job, int $delay = 0): bool
    {
        $this->released[] = $job;
        $job->markReleased();

        return true;
    }

    public function failed(Job $job, Throwable $exception): void
    {
        $this->failed[] = $job;
        $job->markDeleted();
    }

    /** @return list<array{job: Closure, data: array<string, mixed>, queue: string, delay: int}> */
    public function getPushed(): array
    {
        return $this->pushed;
    }

    /** @return list<string> */
    public function getSizeQueues(): array
    {
        return $this->sizeQueues;
    }

    /** @return list<Job> */
    public function getDeletedJobs(): array
    {
        return $this->deleted;
    }

    /** @return list<Job> */
    public function getReleasedJobs(): array
    {
        return $this->released;
    }

    /** @return list<Job> */
    public function getFailedJobs(): array
    {
        return $this->failed;
    }
}