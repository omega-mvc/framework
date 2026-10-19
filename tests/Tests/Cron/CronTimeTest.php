<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Cron\Schedule;
use Omega\Cron\ScheduleTime;
use Omega\Time\Now;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function Omega\Time\now;

#[CoversClass(Schedule::class)]
#[CoversClass(ScheduleTime::class)]
#[CoversClass(Now::class)]
final class CronTimeTest extends TestCase
{
    public function testRunsOnlyJustInTime(): void
    {
        $schedule = new Schedule(now()->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->justInTime()
            ->eventName('test 01');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyEveryTenMinute(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 00:00:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->everyTenMinute()
            ->eventName('test 10 minute');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyEveryThirtyMinutes(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->everyThirtyMinutes()
            ->eventName('test 30 minute');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyEveryTwoHour(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 02:00:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->everyTwoHour()
            ->eventName('test 2 hour');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyEveryTwelveHour(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 12:00:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->everyTwelveHour()
            ->eventName('test 12 hour');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyHourly(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 00:00:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->hourly()
            ->eventName('test hourly');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyHourlyAt(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 05:00:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->hourlyAt(5)
            ->eventName('test hourlyAt 5 hour');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyDaily(): void
    {
        $schedule = new Schedule(new Now('00:00:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->daily()
            ->eventName('test daily');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyDailyAt(): void
    {
        $schedule = new Schedule(new Now('12/12/2012 00:00:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->dailyAt(12)
            ->eventName('test dailyAt 12');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyWeekly(): void
    {
        $schedule = new Schedule(new Now('12/16/2012 00:00:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->weekly()
            ->eventName('test weekly');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }

    public function testRunsOnlyMonthly(): void
    {
        $schedule = new Schedule(new Now('1/1/2012 00:00:00')->getTimestamp());
        $schedule
            ->call(fn (): string => 'due time')
            ->monthly()
            ->eventName('test monthly');

        foreach ($schedule->getPools() as $scheduleItem) {
            $this->assertTrue($scheduleItem->isDue());
        }
    }
}
