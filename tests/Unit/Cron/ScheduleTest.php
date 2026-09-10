<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Cron\InterpolateInterface;
use Omega\Cron\Schedule;
use Omega\Cron\ScheduleTime;
use Omega\Time\Now;

use function ob_get_clean;
use function ob_start;
use function str_repeat;

covers(Schedule::class, ScheduleTime::class, Now::class);

function cronLogger(): InterpolateInterface
{
    return new class implements InterpolateInterface {
        public function interpolate(string $message, array $context = []): void
        {
            echo 'works';
        }
    };
}

it('can continue schedule event job fail', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), cronLogger());

    $schedule
        ->call(function (): void {
            throw new \DivisionByZeroError('division by zero');
        })
        ->everyThirtyMinutes()
        ->eventName('test 30 minute');

    $schedule
        ->call(function (): void {
            return;
        })
        ->everyTenMinute()
        ->eventName('test 10 minute');

    ob_start();
    $schedule->execute();
    $out = ob_get_clean();

    expect($out)->toBe(str_repeat('works', 2));
});

it('can run retry schedule', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), cronLogger());

    $attempts = 0;

    $schedule
        ->call(function () use (&$attempts): void {
            $attempts++;
            throw new \DivisionByZeroError('division by zero');
        })
        ->retry(5)
        ->everyThirtyMinutes()
        ->eventName('test 30 minute');

    $schedule
        ->call(function (): void {
            return;
        })
        ->everyTenMinute()
        ->eventName('test 10 minute');

    ob_start();
    $schedule->execute();
    ob_get_clean();

    expect($attempts)->toBe(5);
});

it('can run retry condition schedule', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), cronLogger());

    $test = 1;

    $schedule
        ->call(function () use (&$test): void {
            $test++;
        })
        ->retryIf(true)
        ->everyThirtyMinutes()
        ->eventName('test 30 minute');

    ob_start();
    $schedule->execute();
    ob_get_clean();

    expect($test)->toBe(3);
});

it('can log cron expect whatever condition', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), cronLogger());

    $schedule
        ->call(function (): void {
            throw new \DivisionByZeroError('division by zero');
        })
        ->retry(20)
        ->everyThirtyMinutes()
        ->eventName('test 30 minute');

    ob_start();
    $schedule->execute();
    $out = ob_get_clean();

    expect($out)->toBe(str_repeat('works', 20));
});

it('can skip schedule event is due', function (): void {
    $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), cronLogger());

    $alwaysFalse = false;

    $schedule
        ->call(function () use (&$alwaysFalse): void {
            $alwaysFalse = true;
        })
        ->justInTime()
        ->skip(fn (): bool => true);

    $schedule->execute();

    expect($alwaysFalse)->toBeFalse();
});