<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Cron\Schedule;
use Omega\Cron\ScheduleTime;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function Omega\Time\now;

#[CoversClass(Schedule::class)]
#[CoversClass(ScheduleTime::class)]
final class BasicCronTest extends TestCase
{
    public function testCorrectsScheduleClass(): void
    {
        $schedule = new Schedule(now()->getTimestamp());
        $schedule->call(fn (): string => 'test')->justInTime();

        $this->assertCount(1, $schedule->getPools());
        $this->assertSame('anonymously', $schedule->getPools()[0]->eventName);
    }

    public function testSchedulesRunAnonymously(): void
    {
        $anonymously = new Schedule(now()->getTimestamp());
        $anonymously
            ->call(fn (): string => 'is run anonymously')
            ->justInTime()
            ->eventName('test 01')
            ->anonymously();

        $anonymously
            ->call(fn (): string => 'is run anonymously')
            ->hourly()
            ->eventName('test 02')
            ->anonymously();

        foreach ($anonymously->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->anonymously);
        }
    }

    public function testCanAddSchedule(): void
    {
        $cron1 = new Schedule(now()->getTimestamp());
        $cron1->call(fn (): bool => true)->eventName('from1');

        $cron2 = new Schedule(now()->getTimestamp());
        $cron2->call(fn (): bool => true)->eventName('from2');

        $cron1->add($cron2);

        $this->assertCount(2, $cron1->getPools());
        $this->assertSame('from1', $cron1->getPools()[0]->eventName);
        $this->assertSame('from2', $cron1->getPools()[1]->eventName);
    }

    public function testCanFlush(): void
    {
        $cron = new Schedule(now()->getTimestamp());
        $cron->call(fn (): bool => true)->eventName('one');
        $cron->call(fn (): bool => true)->eventName('two');

        $this->assertCount(2, $cron->getPools());

        $cron->flush();

        $this->assertCount(0, $cron->getPools());
    }
}
