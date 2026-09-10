<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Cron\Schedule;
use Omega\Cron\ScheduleTime;
use Omega\Time\Now;

use function Omega\Time\now;

covers(Schedule::class, ScheduleTime::class, Now::class);

it('runs only just in time', function (): void {
    $schedule = new Schedule(now()->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->justInTime()
        ->eventName('test 01');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only every ten minute', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 00:00:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->everyTenMinute()
        ->eventName('test 10 minute');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only every thirty minutes', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->everyThirtyMinutes()
        ->eventName('test 30 minute');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only every two hour', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 02:00:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->everyTwoHour()
        ->eventName('test 2 hour');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only every twelve hour', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 12:00:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->everyTwelveHour()
        ->eventName('test 12 hour');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only hourly', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 00:00:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->hourly()
        ->eventName('test hourly');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only hourly at', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 05:00:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->hourlyAt(5)
        ->eventName('test hourlyAt 5 hour');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only daily', function (): void {
    $schedule = new Schedule(new Now('00:00:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->daily()
        ->eventName('test daily');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only daily at', function (): void {
    $schedule = new Schedule(new Now('12/12/2012 00:00:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->dailyAt(12)
        ->eventName('test dailyAt 12');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only weekly', function (): void {
    $schedule = new Schedule(new Now('12/16/2012 00:00:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->weekly()
        ->eventName('test weekly');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});

it('runs only monthly', function (): void {
    $schedule = new Schedule(new Now('1/1/2012 00:00:00')->getTimestamp());
    $schedule
        ->call(fn (): string => 'due time')
        ->monthly()
        ->eventName('test monthly');

    foreach ($schedule->getPools() as $scheduleItem) {
        expect($scheduleItem->isDue())->toBeTrue();
    }
});