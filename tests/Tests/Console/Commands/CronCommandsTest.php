<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Container\Exceptions\CircularAliasException;
use Omega\Cron\InterpolateInterface;
use Omega\Cron\Schedule;
use Omega\Console\Commands\CronCommand;
use Omega\Support\Facades\Schedule as FacadesSchedule;
use PHPUnit\Framework\Attributes\CoversClass;

use function ob_get_clean;
use function ob_start;

/**
 * Test suite for cron-related console commands.
 *
 * This class validates the behavior of the cron command integration with the
 * scheduling system. It ensures that cron commands can be executed, listed,
 * and correctly populated using the Schedule facade.
 *
 * The tests focus on verifying:
 * - Correct execution of the cron command entry points.
 * - Proper registration of scheduled tasks via the facade layer.
 * - Correct propagation of scheduling configuration, such as execution time,
 *   from the application container into the command runtime.
 *
 * A controlled in-memory schedule instance is injected during setup to
 * guarantee deterministic behavior and avoid side effects during test runs.
 *
 * @category   Tests
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Schedule::class)]
#[CoversClass(CronCommand::class)]
#[CoversClass(FacadesSchedule::class)]
final class CronCommandsTest extends AbstractTestCommand
{
    /**
     * Default schedule execution time used for testing.
     *
     * This value represents the time configuration passed to the Schedule
     * instance during test setup. It is later asserted to ensure that the
     * cron command correctly receives and preserves the scheduling interval
     * from the application container.
     */
    private int $time;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
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

    /**
     * Creates a cron command instance with an isolated logger.
     *
     * This helper method returns an anonymous CronCommand implementation
     * configured with a custom logger to suppress output and side effects
     * during test execution. It allows tests to focus exclusively on command
     * behavior without relying on external logging mechanisms.
     *
     * The command is initialized using a simulated CLI argument vector,
     * ensuring consistency across all cron-related test cases.
     *
     * @param string $argv Command-line input string.
     * @return CronCommand A fully initialized cron command instance.
     */
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
