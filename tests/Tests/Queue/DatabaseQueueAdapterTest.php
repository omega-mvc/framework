<?php

declare(strict_types=1);

namespace Tests\Queue;

use Omega\Queue\Adapter\DatabaseQueueAdapter;
use Omega\Queue\Job;
use Omega\SerializableClosure\UnsignedSerializableClosure;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

use function serialize;
use function unserialize;

#[CoversClass(DatabaseQueueAdapter::class)]
#[CoversClass(Job::class)]
final class DatabaseQueueAdapterTest extends TestCase
{
    public function testPushesASerializableJobOntoTheDefaultQueue(): void
    {
        $pdo     = new ScriptedConnection();
        $adapter = new DatabaseQueueAdapter($pdo);

        $job = $adapter->push(static fn (): int => 42);

        $this->assertSame(1, $job->getId());
        $this->assertSame('default', $job->getQueue());
        $this->assertSame(0, $job->getAttempts());
        $this->assertCount(1, $pdo->queries);
        $this->assertStringContainsString('INSERT INTO jobs', $pdo->queries[0]);

        $payload = unserialize($job->getPayload());

        $this->assertInstanceOf(UnsignedSerializableClosure::class, $payload);

        $this->assertSame(42, $payload());
    }

    public function testShiftsAndReservesTheNextAvailableJob(): void
    {
        $pdo = (new ScriptedConnection())->whenQueryContains('SELECT * FROM jobs', [[
            'id'           => '3',
            'queue'        => 'default',
            'payload'      => serialize(new UnsignedSerializableClosure(static fn () => null)),
            'attempts'     => 0,
            'available_at' => '2026-01-01 00:00:00',
            'created_at'   => '2026-01-01 00:00:00',
        ]]);
        $adapter = new DatabaseQueueAdapter($pdo);

        $job = $adapter->shift();

        $this->assertInstanceOf(Job::class, $job);

        $this->assertSame(3, $job->getId());
        $this->assertSame('default', $job->getQueue());
        $this->assertSame(0, $job->getAttempts());
        $this->assertNotNull($job->getReservedAt());
        $this->assertSame('2026-01-01 00:00:00', $job->getCreatedAt());

        $this->assertCount(2, $pdo->queries);
        $this->assertStringContainsString('SELECT * FROM jobs', $pdo->queries[0]);
        $this->assertStringContainsString('SET reserved_at', $pdo->queries[1]);
    }

    public function testReturnsNullWhenNoJobIsAvailable(): void
    {
        $pdo     = (new ScriptedConnection())->setDefaultResultset([]);
        $adapter = new DatabaseQueueAdapter($pdo);

        $this->assertNull($adapter->shift());
    }

    public function testCountsZeroPendingJobs(): void
    {
        $pdo = (new ScriptedConnection())->whenQueryContains('SELECT COUNT(*) FROM jobs', [['COUNT(*)' => 0]]);
        $adapter = new DatabaseQueueAdapter($pdo);

        $this->assertSame(0, $adapter->size());
    }

    public function testCastsTheScriptedCountToAnInteger(): void
    {
        $pdo = (new ScriptedConnection())->whenQueryContains('SELECT COUNT(*) FROM jobs', [['COUNT(*)' => '5']]);
        $adapter = new DatabaseQueueAdapter($pdo);

        $this->assertSame(5, $adapter->size('mail'));
    }

    public function testReturnsZeroWhenTheCountColumnIsMissing(): void
    {
        $pdo = (new ScriptedConnection())->whenQueryContains('SELECT COUNT(*) FROM jobs', [['total' => 5]]);
        $adapter = new DatabaseQueueAdapter($pdo);

        $this->assertSame(0, $adapter->size());
    }

    public function testReturnsZeroWhenTheCountQueryReturnsNoRow(): void
    {
        $pdo     = (new ScriptedConnection())->setDefaultResultset([]);
        $adapter = new DatabaseQueueAdapter($pdo);

        $this->assertSame(0, $adapter->size());
    }

    public function testDeletesAJobFromTheQueue(): void
    {
        $pdo     = new ScriptedConnection();
        $adapter = new DatabaseQueueAdapter($pdo);
        $job     = new Job(
            id: 1,
            queue: 'default',
            payload: 'payload',
            attempts: 0,
            reservedAt: null,
            availableAt: null,
            createdAt: '2026-01-01 00:00:00',
        );

        $this->assertTrue($adapter->delete($job));
        $this->assertTrue($job->isDeleted());
        $this->assertStringContainsString('DELETE FROM jobs', $pdo->queries[0]);
    }

    public function testReleasesAJobBackOntoTheQueue(): void
    {
        $pdo     = new ScriptedConnection();
        $adapter = new DatabaseQueueAdapter($pdo);
        $job     = new Job(
            id: 1,
            queue: 'default',
            payload: 'payload',
            attempts: 0,
            reservedAt: null,
            availableAt: null,
            createdAt: '2026-01-01 00:00:00',
        );

        $this->assertTrue($adapter->release($job, 5));
        $this->assertTrue($job->isReleased());
        $this->assertStringContainsString('SET reserved_at = NULL', $pdo->queries[0]);
    }

    public function testMarksAFailedJobAsDeleted(): void
    {
        $pdo     = new ScriptedConnection();
        $adapter = new DatabaseQueueAdapter($pdo);
        $job     = new Job(
            id: 1,
            queue: 'default',
            payload: 'payload',
            attempts: 0,
            reservedAt: null,
            availableAt: null,
            createdAt: '2026-01-01 00:00:00',
        );

        $adapter->failed($job, new RuntimeException('boom'));

        $this->assertTrue($job->isDeleted());
        $this->assertStringContainsString('DELETE FROM jobs', $pdo->queries[0]);
    }
}
