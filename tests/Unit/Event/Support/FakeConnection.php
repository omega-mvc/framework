<?php

declare(strict_types=1);

namespace Tests\Event\Support;

use Omega\Database\ConnectionInterface;

/**
 * In-memory database connection used to keep Model instances away from a real PDO.
 */
class FakeConnection implements ConnectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getInstance(): self
    {
        return $this;
    }

    /** @var array<int, array<string, float|string|null>> Stored query execution logs. */
    private array $logs = [];

    /**
     * {@inheritdoc}
     */
    public function flushLogs(): void
    {
        $this->logs = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query): self
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bind(string|int $param, mixed $value, int|null $type = null): self
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resultset(): array|false
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function single(): array|false
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId(): string|false
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callable): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function endTransaction(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTransaction(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction(): bool
    {
        return false;
    }
}