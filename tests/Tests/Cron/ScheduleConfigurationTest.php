<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Cron\InterpolateInterface;
use Omega\Cron\Schedule;
use Omega\Cron\ScheduleTime;
use Omega\Time\Now;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function Omega\Time\now;

#[CoversClass(Schedule::class)]
#[CoversClass(ScheduleTime::class)]
final class ScheduleConfigurationTest extends TestCase
{
    public function testAppliesTheLoggerAndTheTimeToItsPool(): void
    {
        $logger = new class implements InterpolateInterface {
            public int $calls = 0;

            public function interpolate(string $message, array $context = []): void
            {
                $this->calls++;
            }
        };

        $executed = false;
        $schedule = new Schedule();
        $schedule
            ->call(function () use (&$executed): void {
                $executed = true;
            })
            ->justInTime()
            ->eventName('test logger');

        $schedule->setTime(now()->getTimestamp());
        $schedule->setLogger($logger);
        $schedule->execute();

        $this->assertTrue($executed);
        $this->assertSame(1, $logger->calls);
    }

    public function testIsNotDueWhenNoExpectationMatchesTheCurrentTime(): void
    {
        $schedule = new Schedule(new Now('09/07/2021 00:05:00')->getTimestamp());
        $schedule->call(fn (): bool => true)->everyTenMinute();

        $this->assertFalse($schedule->getPools()[0]->isDue());
    }

    public function testThePlaceholderInterpolationDoesNothing(): void
    {
        $scheduleTime = new class (fn (): bool => true, [], now()->getTimestamp()) extends ScheduleTime {
            /**
             * @param array<string, mixed> $context
             */
            public function callInterpolate(string $message, array $context): void
            {
                $this->interpolate($message, $context);
            }
        };

        $scheduleTime->callInterpolate('message', ['key' => 'value']);

        $this->assertSame('anonymously', $scheduleTime->eventName);
    }
}
