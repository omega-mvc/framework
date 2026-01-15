<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Cron\InterpolateInterface;
use Omega\Cron\Schedule;
use Omega\Console\Commands\CronCommand;
use Omega\Support\Facades\Schedule as FacadesSchedule;
use PHPUnit\Framework\Attributes\CoversClass;

use function ob_get_clean;
use function ob_start;

#[CoversClass(Schedule::class)]
#[CoversClass(CronCommand::class)]
#[CoversClass(FacadesSchedule::class)]
final class CronCommandsTest extends AbstractTestCommand
{
    private int $time;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $log = new class implements InterpolateInterface {
            /**
             * @param array<string, mixed> $context
             */
            public function interpolate(string $message, array $context = []): void
            {
            }
        };
        $this->time = 10;
        $this->app->set('schedule', fn () => new Schedule($this->time, $log));
        new FacadesSchedule($this->app);
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        FacadesSchedule::flush();
    }

    private function maker(string $argv): CronCommand
    {
        return new class ($this->argv('omega cron')) extends CronCommand {
            public function __construct($argv)
            {
                parent::__construct($argv);
                $this->log = new class implements InterpolateInterface {
                    /**
                     * @param array<string, mixed> $context
                     */
                    public function interpolate(string $message, array $context = []): void
                    {
                    }
                };
            }
        };
    }

    /**
     * Test it can call cron command main.
     *
     * @return void
     */
    public function testItCanCallCronCommandMain(): void
    {
        $cronCommand = $this->maker('omega cron');
        ob_start();
        $exit = $cronCommand->main();
        ob_get_clean();

        $this->assertSuccess($exit);
    }

    /**
     * Test it can call cron command list.
     *
     * @return void
     */
    public function testItCanCallCronCommandList(): void
    {
        $cronCommand = $this->maker('omega cron');
        ob_start();
        $exit = $cronCommand->list();
        ob_get_clean();

        $this->assertSuccess($exit);
    }

    /**
     * Test it can register from facade.
     *
     * @return void
     */
    public function testItCanRegisterFromFacade(): void
    {
        FacadesSchedule::call(static fn (): int => 0)
            ->eventName('from-static')
            ->justInTime();

        $cronCommand = $this->maker('omega cron');
        ob_start();
        $exit = $cronCommand->list();
        $out  = ob_get_clean();

        $this->assertContain('from-static', $out);
        $this->assertContain('cli-schedule', $out);
        $this->assertSuccess($exit);
    }

    /**
     * Tets it can schedule time must equal.
     *
     * @return void
     */
    public function testItCanScheduleTimeMustEqual(): void
    {
        FacadesSchedule::call(static fn (): int => 0)
            ->eventName('from-static')
            ->justInTime();

        $cronCommand = $this->maker('cli cron');

        $schedule = (fn () => $this->{'getSchedule'}())->call($cronCommand);
        $time     = (fn () => $this->{'time'})->call($schedule);

        $this->assertEquals($this->time, $time);
    }
}
