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

class Job
{
    private bool $deleted = false;

    private bool $released = false;

    public function __construct(
        private readonly int $id,
        private readonly string $queue,
        private readonly string $payload,
        private readonly int $attempts,
        private readonly ?string $reservedAt,
        private readonly ?string $availableAt,
        private readonly string $createdAt,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getReservedAt(): ?string
    {
        return $this->reservedAt;
    }

    public function getAvailableAt(): ?string
    {
        return $this->availableAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function isReleased(): bool
    {
        return $this->released;
    }

    public function markDeleted(): void
    {
        $this->deleted = true;
    }

    public function markReleased(): void
    {
        $this->released = true;
    }

    public function getRawBody(): mixed
    {
        return unserialize($this->payload);
    }
}
