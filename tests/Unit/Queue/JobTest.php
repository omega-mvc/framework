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

use Omega\Queue\Job;
use Omega\SerializableClosure\UnsignedSerializableClosure;

use function expect;
use function is_object;
use function method_exists;
use function serialize;

covers(Job::class);

it('exposes the properties passed to the constructor', function (): void {
    $job = new Job(
        id: 7,
        queue: 'mail',
        payload: 'payload-data',
        attempts: 3,
        reservedAt: '2026-01-02 10:00:00',
        availableAt: null,
        createdAt: '2026-01-01 10:00:00',
    );

    expect($job->getId())->toBe(7);
    expect($job->getQueue())->toBe('mail');
    expect($job->getPayload())->toBe('payload-data');
    expect($job->getAttempts())->toBe(3);
    expect($job->getReservedAt())->toBe('2026-01-02 10:00:00');
    expect($job->getAvailableAt())->toBeNull();
    expect($job->getCreatedAt())->toBe('2026-01-01 10:00:00');
});

it('resolves a serialized closure from the payload', function (): void {
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

    expect(is_object($body))->toBeTrue();

    if (is_object($body)) {
        expect(method_exists($body, '__invoke'))->toBeTrue();
    }

    if ($body instanceof UnsignedSerializableClosure) {
        expect($body())->toBe('lavorato');
    }
});

it('unserializes an array payload as-is', function (): void {
    $job = new Job(
        id: 2,
        queue: 'default',
        payload: serialize(['x' => 1]),
        attempts: 0,
        reservedAt: null,
        availableAt: null,
        createdAt: '2026-01-01 00:00:00',
    );

    expect($job->getRawBody())->toBe(['x' => 1]);
});

it('tracks the deleted state of a job', function (): void {
    $job = new Job(
        id: 1,
        queue: 'default',
        payload: 'payload',
        attempts: 0,
        reservedAt: null,
        availableAt: null,
        createdAt: '2026-01-01 00:00:00',
    );

    expect($job->isDeleted())->toBeFalse();

    $job->markDeleted();

    expect($job->isDeleted())->toBeTrue();
});

it('tracks the released state of a job', function (): void {
    $job = new Job(
        id: 1,
        queue: 'default',
        payload: 'payload',
        attempts: 0,
        reservedAt: null,
        availableAt: null,
        createdAt: '2026-01-01 00:00:00',
    );

    expect($job->isReleased())->toBeFalse();

    $job->markReleased();

    expect($job->isReleased())->toBeTrue();
});