<?php

declare(strict_types=1);

namespace Tests\Event\Support;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * In-memory PSR-3 logger that records every log entry for assertions.
 */
class MemoryLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<array-key, mixed>}> */
    private array $records = [];

    /**
     * Records a log entry in memory.
     *
     * @param mixed                $level   The log level.
     * @param string|Stringable    $message The log message.
     * @param array<array-key, mixed> $context Additional context data.
     * @return void
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level'   => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * Returns all recorded log entries.
     *
     * @return list<array{level: mixed, message: string, context: array<array-key, mixed>}>
     */
    public function getRecords(): array
    {
        return $this->records;
    }
}