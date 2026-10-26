<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Application\Application;
use Omega\Cron\CronServiceProvider;
use Omega\Cron\Log;
use Omega\Cron\Schedule;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(CronServiceProvider::class)]
final class CronServiceProviderTest extends TestCase
{
    public function testRegistersTheCronLoggerAndTheSchedule(): void
    {
        $app = new Application(dirname(__DIR__, 3) . '/');

        (new CronServiceProvider($app))->boot();

        $logger = $app->get('cron.log');
        $this->assertInstanceOf(Log::class, $logger);

        $schedule = $app->get('schedule');
        $this->assertInstanceOf(Schedule::class, $schedule);

        $executed = false;
        $schedule->call(function () use (&$executed): void {
            $executed = true;
        })
            ->justInTime()
            ->anonymously();

        $schedule->execute();

        $this->assertTrue($executed);
    }

    public function testBuildsAScheduleWithoutALoggerWhenTheCronLoggerIsNotInterpolable(): void
    {
        $app = new Application(dirname(__DIR__, 3) . '/');
        $app->set('cron.log', static fn (): object => new class () {
        });

        (new CronServiceProvider($app))->boot();

        $this->assertInstanceOf(Schedule::class, $app->get('schedule'));
    }
}
