<?php

/**
 * Part of Omega - Tests\Queue Package.
 * @link https://omega-mvc.github.io
 * @author Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version 2.0.0
 */

declare(strict_types=1);

namespace Tests\Queue;

use Omega\Queue\Adapter\DatabaseQueueAdapter;
use Omega\Queue\Job;
use Omega\SerializableClosure\UnsignedSerializableClosure;
use RuntimeException;
use Tests\Console\Commands\Fixtures\ScriptedConnection;

use function expect;
use function is_object;
use function method_exists;
use function serialize;
use function unserialize;

covers(DatabaseQueueAdapter::class);
covers(Job::class);

it('pushes a serializable job onto the default queue', function (): void {
    $pdo     = new ScriptedConnection();
    $adapter = new DatabaseQueueAdapter($pdo);

    $job = $adapter->push(static fn (): int => 42);

    expect($job->getId())->toBe(1);
    expect($job->getQueue())->toBe('default');
    expect($job->getAttempts())->toBe(0);
    expect($pdo->queries)->toHaveCount(1);
    expect($pdo->queries[0])->toContain('INSERT INTO jobs');

    $payload = unserialize($job->getPayload());

    expect(is_object($payload))->toBeTrue();

    if (is_object($payload)) {
        expect(method_exists($payload, '__invoke'))->toBeTrue();
    }

    if ($payload instanceof UnsignedSerializableClosure) {
        expect($payload())->toBe(42);
    }
});

it('shifts and reserves the next available job', function (): void {
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

    expect($job)->toBeInstanceOf(Job::class);

    if ($job instanceof Job) {
        expect($job->getId())->toBe(3);
        expect($job->getQueue())->toBe('default');
        expect($job->getAttempts())->toBe(0);
        expect($job->getReservedAt())->not->toBeNull();
        expect($job->getCreatedAt())->toBe('2026-01-01 00:00:00');
    }

    expect($pdo->queries)->toHaveCount(2);
    expect($pdo->queries[0])->toContain('SELECT * FROM jobs');
    expect($pdo->queries[1])->toContain('SET reserved_at');
});

it('returns null when no job is available', function (): void {
    $pdo     = (new ScriptedConnection())->setDefaultResultset([]);
    $adapter = new DatabaseQueueAdapter($pdo);

    expect($adapter->shift())->toBeNull();
});

it('counts zero pending jobs', function (): void {
    $pdo = (new ScriptedConnection())->whenQueryContains('SELECT COUNT(*) FROM jobs', [['COUNT(*)' => 0]]);
    $adapter = new DatabaseQueueAdapter($pdo);

    expect($adapter->size())->toBe(0);
});

it('casts the scripted count to an integer', function (): void {
    $pdo = (new ScriptedConnection())->whenQueryContains('SELECT COUNT(*) FROM jobs', [['COUNT(*)' => '5']]);
    $adapter = new DatabaseQueueAdapter($pdo);

    expect($adapter->size('mail'))->toBe(5);
});

it('returns zero when the count column is missing', function (): void {
    $pdo = (new ScriptedConnection())->whenQueryContains('SELECT COUNT(*) FROM jobs', [['total' => 5]]);
    $adapter = new DatabaseQueueAdapter($pdo);

    expect($adapter->size())->toBe(0);
});

it('deletes a job from the queue', function (): void {
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

    expect($adapter->delete($job))->toBeTrue();
    expect($job->isDeleted())->toBeTrue();
    expect($pdo->queries[0])->toContain('DELETE FROM jobs');
});

it('releases a job back onto the queue', function (): void {
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

    expect($adapter->release($job, 5))->toBeTrue();
    expect($job->isReleased())->toBeTrue();
    expect($pdo->queries[0])->toContain('SET reserved_at = NULL');
});

it('marks a failed job as deleted', function (): void {
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

    expect($job->isDeleted())->toBeTrue();
    expect($pdo->queries[0])->toContain('DELETE FROM jobs');
});