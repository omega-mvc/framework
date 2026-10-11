<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\CronListCommand;
use Omega\Cron\Facade\Schedule as Scheduler;
use Omega\Cron\Schedule;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(CronListCommand::class)]
final class CronListCommandTest extends TestCase
{
    public function testReportsWhenThereAreNoScheduledJobs(): void
    {
        $base = sys_get_temp_dir() . '/omegacl-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('schedule', new Schedule(time()));

        AbstractFacade::flushInstance();
        Scheduler::setFacadeBase($app);

        $command = new CronListCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('No scheduled jobs found.', $result->getDisplay());

        $app->flush();
    }

    public function testListsTheScheduledJobs(): void
    {
        $base = sys_get_temp_dir() . '/omegacl-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $schedule = new Schedule(time());
        $schedule->call(static function (): void {})->hourly()->eventName('Send mail');
        $schedule->call(static function (): void {})->justInTime()->anonymously()->eventName('Backup DB');

        $app->set('schedule', $schedule);

        AbstractFacade::flushInstance();
        Scheduler::setFacadeBase($app);

        $command = new CronListCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Send mail', $display);
        $this->assertStringContainsString('Backup DB', $display);
        $this->assertStringContainsString('hourly', $display);
        $this->assertStringContainsString('justInTime', $display);
        $this->assertStringContainsString('(Anonymous)', $display);
        $this->assertStringContainsString('Showing [2] scheduled jobs.', $display);

        $app->flush();
    }
}
