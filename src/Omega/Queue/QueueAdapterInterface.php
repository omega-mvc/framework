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
use Throwable;

interface QueueAdapterInterface
{
    /**
     * Push a new job onto the queue.
     *
     * @param Closure            $job   The closure to execute when the job is processed.
     * @param array<string, mixed> $data Optional payload data attached to the job.
     * @param string             $queue The queue name.
     * @param int                $delay Delay in seconds before the job becomes available.
     */
    public function push(Closure $job, array $data = [], string $queue = 'default', int $delay = 0): Job;

    public function shift(): ?Job;

    public function size(string $queue = 'default'): int;

    public function delete(Job $job): bool;

    public function release(Job $job, int $delay = 0): bool;

    public function failed(Job $job, Throwable $exception): void;
}
