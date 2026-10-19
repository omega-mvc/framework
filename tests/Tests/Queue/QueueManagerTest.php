<?php

declare(strict_types=1);

namespace Tests\Queue;

use Omega\Queue\Exception\UnknownDriverException;
use Omega\Queue\Job;
use Omega\Queue\QueueManager;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\Queue\Support\FakeQueueAdapter;
use Tests\TestCase;

#[CoversClass(QueueManager::class)]
#[CoversClass(UnknownDriverException::class)]
final class QueueManagerTest extends TestCase
{
    public function testReturnsTheDriverRegisteredAsDefault(): void
    {
        $default = new FakeQueueAdapter();
        $manager = new QueueManager('default', $default);

        $this->assertSame($default, $manager->getDriver());
    }

    public function testReturnsADriverRegisteredByName(): void
    {
        $default = new FakeQueueAdapter();
        $mail    = new FakeQueueAdapter();
        $manager = new QueueManager('default', $default)->setDriver('mail', $mail);

        $this->assertSame($mail, $manager->getDriver('mail'));
    }

    public function testThrowsWhenTheRequestedDriverIsNotRegistered(): void
    {
        $manager = new QueueManager('default', new FakeQueueAdapter());

        $this->expectException(UnknownDriverException::class);

        $manager->getDriver('unknown');
    }

    public function testExposesTheRegisteredDriverNames(): void
    {
        $manager = new QueueManager('default', new FakeQueueAdapter())
            ->setDriver('mail', new FakeQueueAdapter());

        $this->assertContains('default', $manager->getDriverNames());
        $this->assertContains('mail', $manager->getDriverNames());
    }

    public function testReturnsTheDefaultDriverNameConfiguredInTheConstructor(): void
    {
        $manager = new QueueManager('primary', new FakeQueueAdapter());

        $this->assertSame('primary', $manager->getDefaultDriverName());
    }

    public function testResolvesClosureDriversOnceAndCachesTheResolvedInstance(): void
    {
        $resolved = 0;
        $manager  = new QueueManager('default', new FakeQueueAdapter());

        $manager->setDriver('lazy', static function () use (&$resolved): FakeQueueAdapter {
            $resolved++;

            return new FakeQueueAdapter();
        });

        $first  = $manager->getDriver('lazy');
        $second = $manager->getDriver('lazy');

        $this->assertSame(1, $resolved);
        $this->assertSame($first, $second);
    }

    public function testDelegatesPushWithAnExplicitQueueAndDelay(): void
    {
        $default = new FakeQueueAdapter();
        $manager = new QueueManager('default', $default);

        $job = $manager->push(static fn () => null, ['a' => 1], 'mail', 5);

        $this->assertSame(1, $job->getId());
        $this->assertSame('mail', $job->getQueue());
        $this->assertCount(1, $default->getPushed());
        $this->assertSame('mail', $default->getPushed()[0]['queue']);
        $this->assertSame(5, $default->getPushed()[0]['delay']);
        $this->assertSame(['a' => 1], $default->getPushed()[0]['data']);
    }

    public function testDelegatesPushUsingTheDefaultQueueWhenNoneIsGiven(): void
    {
        $default = new FakeQueueAdapter();
        $manager = new QueueManager('primary', $default);

        $manager->push(static fn () => null);

        $this->assertCount(1, $default->getPushed());
        $this->assertSame('primary', $default->getPushed()[0]['queue']);
        $this->assertSame(0, $default->getPushed()[0]['delay']);
    }

    public function testDelegatesShiftToTheDefaultDriver(): void
    {
        $default = new FakeQueueAdapter();
        $manager = new QueueManager('default', $default);
        $pushed  = $default->push(static fn () => null);

        $this->assertSame($pushed, $manager->shift());
        $this->assertNull($manager->shift());
    }

    public function testDelegatesSizeUsingTheDefaultQueueWhenNoneIsGiven(): void
    {
        $default = new FakeQueueAdapter();
        $manager = new QueueManager('primary', $default);

        $default->push(static fn () => null);
        $default->push(static fn () => null);

        $this->assertSame(2, $manager->size());
        $this->assertSame(2, $manager->size('mail'));
        $this->assertSame(['primary', 'mail'], $default->getSizeQueues());
    }

    public function testDelegatesDeleteReleaseAndFailedToTheDefaultDriver(): void
    {
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

        $this->assertTrue($manager->delete($job));
        $this->assertCount(1, $default->getDeletedJobs());
        $this->assertSame($job, $default->getDeletedJobs()[0]);

        $this->assertTrue($manager->release($job));
        $this->assertCount(1, $default->getReleasedJobs());
        $this->assertSame($job, $default->getReleasedJobs()[0]);

        $manager->failed($job, new RuntimeException('boom'));

        $this->assertCount(1, $default->getFailedJobs());
        $this->assertSame($job, $default->getFailedJobs()[0]);
    }
}
