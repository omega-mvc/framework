<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Cron\Schedule;
use Omega\Cron\ScheduleTime;

use function Omega\Time\now;

covers(Schedule::class, ScheduleTime::class);

it('corrects schedule class', function (): void {
    $schedule = new Schedule(now()->getTimestamp());
    $schedule->call(fn (): string => 'test')->justInTime();

    expect($schedule->getPools())->toHaveCount(1);
    expect($schedule->getPools()[0]->eventName)->toBe('anonymously');
});

it('schedules run anonymously', function (): void {
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
        expect($scheduleItem->anonymously)->toBeTrue();
    }
});

it('can add schedule', function (): void {
    $cron1 = new Schedule(now()->getTimestamp());
    $cron1->call(fn (): bool => true)->eventName('from1');

    $cron2 = new Schedule(now()->getTimestamp());
    $cron2->call(fn (): bool => true)->eventName('from2');

    $cron1->add($cron2);

    expect($cron1->getPools())->toHaveCount(2);
    expect($cron1->getPools()[0]->eventName)->toBe('from1');
    expect($cron1->getPools()[1]->eventName)->toBe('from2');
});

it('can flush', function (): void {
    $cron = new Schedule(now()->getTimestamp());
    $cron->call(fn (): bool => true)->eventName('one');
    $cron->call(fn (): bool => true)->eventName('two');

    expect($cron->getPools())->toHaveCount(2);

    $cron->flush();

    expect($cron->getPools())->toHaveCount(0);
});