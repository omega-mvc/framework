<?php

declare(strict_types=1);

namespace Tests\Queue;

use Omega\Queue\Job;
use Omega\SerializableClosure\UnsignedSerializableClosure;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function is_object;
use function method_exists;
use function serialize;

#[CoversClass(Job::class)]
final class JobTest extends TestCase
{
    public function testExposesThePropertiesPassedToTheConstructor(): void
    {
        $job = new Job(
            id: 7,
            queue: 'mail',
            payload: 'payload-data',
            attempts: 3,
            reservedAt: '2026-01-02 10:00:00',
            availableAt: null,
            createdAt: '2026-01-01 10:00:00',
        );

        $this->assertSame(7, $job->getId());
        $this->assertSame('mail', $job->getQueue());
        $this->assertSame('payload-data', $job->getPayload());
        $this->assertSame(3, $job->getAttempts());
        $this->assertSame('2026-01-02 10:00:00', $job->getReservedAt());
        $this->assertNull($job->getAvailableAt());
        $this->assertSame('2026-01-01 10:00:00', $job->getCreatedAt());
    }

    public function testResolvesASerializedClosureFromThePayload(): void
    {
        $job = new Job(
            id: 1,
            queue: 'default',
            payload: serialize(new UnsignedSerializableClosure(fn (): string => 'lavorato')),
            attempts: 0,
            reservedAt: null,
            availableAt: null,
            createdAt: '2026-01-01 00:00:00',
        );

        $body = $job->getRawBody();

        $this->assertTrue(is_object($body));

        if (is_object($body)) {
            $this->assertTrue(method_exists($body, '__invoke'));
        }

        if ($body instanceof UnsignedSerializableClosure) {
            $this->assertSame('lavorato', $body());
        }
    }

    public function testUnserializesAnArrayPayloadAsIs(): void
    {
        $job = new Job(
            id: 2,
            queue: 'default',
            payload: serialize(['x' => 1]),
            attempts: 0,
            reservedAt: null,
            availableAt: null,
            createdAt: '2026-01-01 00:00:00',
        );

        $this->assertSame(['x' => 1], $job->getRawBody());
    }

    public function testTracksTheDeletedStateOfAJob(): void
    {
        $job = new Job(
            id: 1,
            queue: 'default',
            payload: 'payload',
            attempts: 0,
            reservedAt: null,
            availableAt: null,
            createdAt: '2026-01-01 00:00:00',
        );

        $this->assertFalse($job->isDeleted());

        $job->markDeleted();

        $this->assertTrue($job->isDeleted());
    }

    public function testTracksTheReleasedStateOfAJob(): void
    {
        $job = new Job(
            id: 1,
            queue: 'default',
            payload: 'payload',
            attempts: 0,
            reservedAt: null,
            availableAt: null,
            createdAt: '2026-01-01 00:00:00',
        );

        $this->assertFalse($job->isReleased());

        $job->markReleased();

        $this->assertTrue($job->isReleased());
    }
}
