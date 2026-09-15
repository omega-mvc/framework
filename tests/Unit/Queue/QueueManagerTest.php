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

use Omega\Queue\Exception\UnknownDriverException;
use Omega\Queue\Job;
use Omega\Queue\QueueManager;
use RuntimeException;
use Tests\Queue\Support\FakeQueueAdapter;

use function expect;

covers(QueueManager::class);
covers(UnknownDriverException::class);

it('returns the driver registered as default', function (): void {
    $default = new FakeQueueAdapter();
    $manager = new QueueManager('default', $default);

    expect($manager->getDriver())->toBe($default);
});

it('returns a driver registered by name', function (): void {
    $default = new FakeQueueAdapter();
    $mail    = new FakeQueueAdapter();
    $manager = new QueueManager('default', $default)->setDriver('mail', $mail);

    expect($manager->getDriver('mail'))->toBe($mail);
});

it('throws when the requested driver is not registered', function (): void {
    $manager = new QueueManager('default', new FakeQueueAdapter());

    expect(
        fn () => $manager->getDriver('unknown'),
    )->toThrow(UnknownDriverException::class);
});

it('exposes the registered driver names', function (): void {
    $manager = new QueueManager('default', new FakeQueueAdapter())
        ->setDriver('mail', new FakeQueueAdapter());

    expect($manager->getDriverNames())->toContain('default');
    expect($manager->getDriverNames())->toContain('mail');
});

it('returns the default driver name configured in the constructor', function (): void {
    $manager = new QueueManager('primary', new FakeQueueAdapter());

    expect($manager->getDefaultDriverName())->toBe('primary');
});

it('resolves closure drivers once and caches the resolved instance', function (): void {
    $resolved = 0;
    $manager  = new QueueManager('default', new FakeQueueAdapter());

    $manager->setDriver('lazy', static function () use (&$resolved): FakeQueueAdapter {
        $resolved++;

        return new FakeQueueAdapter();
    });

    $first  = $manager->getDriver('lazy');
    $second = $manager->getDriver('lazy');

    expect($resolved)->toBe(1);
    expect($first)->toBe($second);
});

it('delegates push with an explicit queue and delay', function (): void {
    $default = new FakeQueueAdapter();
    $manager = new QueueManager('default', $default);

    $job = $manager->push(static fn () => null, ['a' => 1], 'mail', 5);

    expect($job->getId())->toBe(1);
    expect($job->getQueue())->toBe('mail');
    expect($default->getPushed())->toHaveCount(1);
    expect($default->getPushed()[0]['queue'])->toBe('mail');
    expect($default->getPushed()[0]['delay'])->toBe(5);
    expect($default->getPushed()[0]['data'])->toBe(['a' => 1]);
});

it('delegates push using the default queue when none is given', function (): void {
    $default = new FakeQueueAdapter();
    $manager = new QueueManager('primary', $default);

    $manager->push(static fn () => null);

    expect($default->getPushed())->toHaveCount(1);
    expect($default->getPushed()[0]['queue'])->toBe('primary');
    expect($default->getPushed()[0]['delay'])->toBe(0);
});

it('delegates shift to the default driver', function (): void {
    $default = new FakeQueueAdapter();
    $manager = new QueueManager('default', $default);
    $pushed  = $default->push(static fn () => null);

    expect($manager->shift())->toBe($pushed);
    expect($manager->shift())->toBeNull();
});

it('delegates size using the default queue when none is given', function (): void {
    $default = new FakeQueueAdapter();
    $manager = new QueueManager('primary', $default);

    $default->push(static fn () => null);
    $default->push(static fn () => null);

    expect($manager->size())->toBe(2);
    expect($manager->size('mail'))->toBe(2);
    expect($default->getSizeQueues())->toBe(['primary', 'mail']);
});

it('delegates delete, release and failed to the default driver', function (): void {
    $default = new FakeQueueAdapter();
    $manager = new QueueManager('default', $default);
    $job     = new Job(
        id: 1,
        queue: 'default',
        payload: 'payload',
        attempts: 0,
        reservedAt: null,
        availableAt: null,
        createdAt: '2026-01-01 00:00:00',
    );

    expect($manager->delete($job))->toBeTrue();
    expect($default->getDeletedJobs())->toHaveCount(1);
    expect($default->getDeletedJobs()[0])->toBe($job);

    expect($manager->release($job))->toBeTrue();
    expect($default->getReleasedJobs())->toHaveCount(1);
    expect($default->getReleasedJobs()[0])->toBe($job);

    $manager->failed($job, new RuntimeException('boom'));

    expect($default->getFailedJobs())->toHaveCount(1);
    expect($default->getFailedJobs()[0])->toBe($job);
});