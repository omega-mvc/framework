<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\CronWorkCommand;
use Omega\Cron\Facade\Schedule as Scheduler;
use Omega\Cron\Schedule;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(CronWorkCommand::class)]
final class CronWorkCommandTest extends TestCase
{
    public function testExitsImmediatelyWhenTheWorkerFlagIsAlreadySet(): void
    {
        $base = sys_get_temp_dir() . '/omegacw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('schedule', new Schedule(time()));

        AbstractFacade::flushInstance();
        Scheduler::setFacadeBase($app);

        $command = new CronWorkCommand();
        $command->app = $app;

        (new ReflectionClass($command))->getProperty('shouldExit')->setValue($command, true);

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Omega Cron Worker', $result->getDisplay());
        $this->assertStringContainsString(
            'Watching and executing scheduled jobs every minute...',
            $result->getDisplay()
        );
        $this->assertStringContainsString('Press CTRL+C to stop the worker.', $result->getDisplay());

        $app->flush();
    }

    public function testRunsASchedulerCycleUntilItIsInterrupted(): void
    {
        if (!function_exists('pcntl_alarm') || !function_exists('posix_kill')) {
            self::markTestSkipped('Requires the pcntl extension to stop the worker loop.');
        }

        $base = sys_get_temp_dir() . '/omegacw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('schedule', new Schedule(time()));

        AbstractFacade::flushInstance();
        Scheduler::setFacadeBase($app);

        pcntl_async_signals(true);
        pcntl_signal(SIGALRM, static function (): void {
            posix_kill(posix_getpid(), SIGINT);
        });
        pcntl_alarm(1);

        try {
            $command = new CronWorkCommand(1);
            $command->app = $app;

            $result = (new CommandTester($command))->run([]);
        } finally {
            pcntl_alarm(0);
            pcntl_signal(SIGALRM, SIG_DFL);
        }

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Running scheduled tasks...', $display);
        $this->assertStringContainsString('Done! (', $display);

        $app->flush();
    }
}
