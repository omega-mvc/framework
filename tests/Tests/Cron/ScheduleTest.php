<?php

declare(strict_types=1);

namespace Tests\Cron;

use DivisionByZeroError;
use Omega\Cron\InterpolateInterface;
use Omega\Cron\Schedule;
use Omega\Cron\ScheduleTime;
use Omega\Time\Now;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function ob_get_clean;
use function ob_start;
use function str_repeat;

#[CoversClass(Schedule::class)]
#[CoversClass(ScheduleTime::class)]
#[CoversClass(Now::class)]
final class ScheduleTest extends TestCase
{
    public function testCanContinueScheduleEventJobFail(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), $this->cronLogger());

        $schedule
            ->call(function (): void {
                throw new DivisionByZeroError('division by zero');
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

        $this->assertSame(str_repeat('works', 2), $out);
    }

    public function testCanRunRetrySchedule(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), $this->cronLogger());

        $attempts = 0;

        $schedule
            ->call(function () use (&$attempts): void {
                $attempts++;
                throw new DivisionByZeroError('division by zero');
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

        $this->assertSame(5, $attempts);
    }

    public function testCanRunRetryConditionSchedule(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), $this->cronLogger());

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

        $this->assertSame(3, $test);
    }

    public function testCanLogCronExpectWhateverCondition(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), $this->cronLogger());

        $schedule
            ->call(function (): void {
                throw new DivisionByZeroError('division by zero');
            })
            ->retry(20)
            ->everyThirtyMinutes()
            ->eventName('test 30 minute');

        ob_start();
        $schedule->execute();
        $out = ob_get_clean();

        $this->assertSame(str_repeat('works', 20), $out);
    }

    public function testCanSkipScheduleEventIsDue(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 00:30:00')->getTimestamp(), $this->cronLogger());

        $alwaysFalse = false;

        $schedule
            ->call(function () use (&$alwaysFalse): void {
                $alwaysFalse = true;
            })
            ->justInTime()
            ->skip(fn (): bool => true);

        $schedule->execute();

        $this->assertFalse($alwaysFalse);
    }

    private function cronLogger(): InterpolateInterface
    {
        return new class implements InterpolateInterface {
            public function interpolate(string $message, array $context = []): void
            {
                echo 'works';
            }
        };
    }
}
