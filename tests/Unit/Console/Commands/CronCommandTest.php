<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\CronCommand;
use Omega\Cron\Facade\Schedule as Scheduler;
use Omega\Cron\Schedule;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\FakeCronLogger;
use Tests\TestCase;

#[CoversClass(CronCommand::class)]
final class CronCommandTest extends TestCase
{
    public function testExecutesScheduledJobsForCronRun(): void
    {
        $base = sys_get_temp_dir() . '/omegacr-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $executed = false;

        $schedule = new Schedule(time());
        $schedule->setLogger(new FakeCronLogger());
        $schedule->call(static function () use (&$executed): void {
            $executed = true;
        })->justInTime()->eventName('notify');

        $app->set('schedule', $schedule);

        AbstractFacade::flushInstance();
        Scheduler::setFacadeBase($app);

        $command = new CronCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Cron jobs executed successfully in', $result->getDisplay());
        $this->assertTrue($executed);

        $app->flush();
    }

    public function testSucceedsWhenThereAreNoScheduledJobs(): void
    {
        $base = sys_get_temp_dir() . '/omegacr-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('schedule', new Schedule(time()));

        AbstractFacade::flushInstance();
        Scheduler::setFacadeBase($app);

        $command = new CronCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Cron jobs executed successfully in', $result->getDisplay());

        $app->flush();
    }
}
