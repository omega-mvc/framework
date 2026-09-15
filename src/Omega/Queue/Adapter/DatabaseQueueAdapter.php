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

namespace Omega\Queue\Adapter;

use Closure;
use Omega\Database\ConnectionInterface;
use Omega\Queue\Job;
use Omega\Queue\QueueAdapterInterface;
use Omega\SerializableClosure\UnsignedSerializableClosure;
use Throwable;

use function date;
use function is_numeric;
use function serialize;
use function time;

class DatabaseQueueAdapter implements QueueAdapterInterface
{
    public function __construct(
        private ConnectionInterface $pdo,
        private string $table = 'jobs',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function push(Closure $job, array $data = [], string $queue = 'default', int $delay = 0): Job
    {
        $payload = serialize(new UnsignedSerializableClosure($job));
        $availableAt = date('Y-m-d H:i:s', time() + $delay);
        $createdAt = date('Y-m-d H:i:s');

        $this->execute(
            "INSERT INTO {$this->table} (queue, payload, attempts, reserved_at, available_at, created_at) VALUES (?, ?, 0, NULL, ?, ?)",
            [$queue, $payload, $availableAt, $createdAt],
        );

        $lastId = $this->pdo->lastInsertId();

        return new Job(
            id: (int) $lastId,
            queue: $queue,
            payload: $payload,
            attempts: 0,
            reservedAt: null,
            availableAt: $availableAt,
            createdAt: $createdAt,
        );
    }

    public function shift(): ?Job
    {
        $now = date('Y-m-d H:i:s');

        $row = $this->pdo
            ->query("SELECT * FROM {$this->table} WHERE queue = ? AND reserved_at IS NULL AND available_at <= ? ORDER BY id ASC LIMIT 1")
            ->bind(1, 'default')
            ->bind(2, $now)
            ->single();

        if ($row === false) {
            return null;
        }

        /** @var array{id: int|string, queue: string, payload: string, attempts: int, available_at: string, created_at: string} $row */
        $reservedAt = date('Y-m-d H:i:s');

        $this->execute(
            "UPDATE {$this->table} SET reserved_at = ? WHERE id = ?",
            [$reservedAt, $row['id']],
        );

        return new Job(
            id: (int) $row['id'],
            queue: $row['queue'],
            payload: $row['payload'],
            attempts: (int) $row['attempts'],
            reservedAt: $reservedAt,
            availableAt: $row['available_at'],
            createdAt: $row['created_at'],
        );
    }

    public function size(string $queue = 'default'): int
    {
        $now = date('Y-m-d H:i:s');

        $row = $this->pdo
            ->query("SELECT COUNT(*) FROM {$this->table} WHERE queue = ? AND reserved_at IS NULL AND available_at <= ?")
            ->bind(1, $queue)
            ->bind(2, $now)
            ->single();

        if ($row === false) {
            return 0;
        }

        $count = $row['COUNT(*)'] ?? 0;

        return is_numeric($count) ? (int) $count : 0;
    }

    public function delete(Job $job): bool
    {
        $this->execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$job->getId()],
        );

        $job->markDeleted();

        return true;
    }

    public function release(Job $job, int $delay = 0): bool
    {
        $availableAt = date('Y-m-d H:i:s', time() + $delay);

        $this->execute(
            "UPDATE {$this->table} SET reserved_at = NULL, available_at = ?, attempts = attempts + 1 WHERE id = ?",
            [$availableAt, $job->getId()],
        );

        $job->markReleased();

        return true;
    }

    public function failed(Job $job, Throwable $exception): void
    {
        $this->execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$job->getId()],
        );

        $job->markDeleted();
    }

    /** @param list<mixed> $bindings */
    private function execute(string $sql, array $bindings = []): void
    {
        $this->pdo->query($sql);

        foreach ($bindings as $index => $value) {
            $this->pdo->bind($index + 1, $value);
        }

        $this->pdo->execute();
    }
}
