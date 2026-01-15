<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Style\Style;
use Omega\Cron\InterpolateInterface;
use Omega\Cron\Schedule;
use Omega\Support\Facades\Schedule as Scheduler;
use Omega\Time\Now;

use function max;
use function microtime;
use function Omega\Console\info;
use function round;
use function strlen;

/**
 * Command to manage scheduled cron jobs.
 *
 * Provides functionality to run all scheduled jobs, list them, and simulate
 * cron execution in the terminal in real-time.
 *
 * Supports the following command patterns:
 * - `cron`      : Run all scheduled cron jobs.
 * - `cron:list` : Display the list of scheduled jobs.
 * - `cron:work` : Run a simulated cron job loop in the terminal.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class CronCommand extends AbstractCommand
{
    /**
     * Logger for interpolated messages.
     *
     * This logger allows messages to be formatted with placeholders
     * and context values.
     *
     * @var InterpolateInterface
     */
    protected InterpolateInterface $log;

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'cron',
            'fn'      => [self::class, 'main'],
        ], [
            'pattern' => 'cron:list',
            'fn'      => [self::class, 'list'],
        ], [
            'pattern' => 'cron:work',
            'fn'      => [self::class, 'work'],
        ],
    ];

    /**
     * Returns a description of the command, its options, and their relations.
     *
     * This is used to generate help output for users.
     *
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp(): array
    {
        return [
            'commands'  => [
                'cron'      => 'Run cron job (all schedule)',
                'cron:work' => 'Run virtual cron job in terminal (async)',
                'cron:list' => 'Get list of schedule',
            ],
            'options'   => [],
            'relation'  => [],
        ];
    }

    /**
     * Runs all scheduled cron jobs once.
     *
     * Measures the execution time of the schedule and outputs the duration.
     *
     * @return int Exit code: always 0.
     */
    public function main(): int
    {
        $watchStart = microtime(true);

        $this->getSchedule()->execute();

        $watchEnd = round(microtime(true) - $watchStart, 3) * 1000;
        info('done in')
            ->push($watchEnd . 'ms')->textGreen()
            ->out();

        return 0;
    }

    /**
     * Lists all scheduled cron jobs in a formatted table.
     *
     * Displays each job's time, name, and whether it runs anonymously.
     * Execution time for listing is measured and displayed.
     *
     * @return int Exit code: always 0.
     */
    public function list(): int
    {
        $watchStart = microtime(true);
        $print      = new Style("\n");

        $info = [];
        $max  = 0;
        foreach ($this->getSchedule()->getPools() as $cron) {
            $time   = $cron->timeName;
            $name   = $cron->eventName;
            $info[] = [
                'time'        => $time,
                'name'        => $name,
                'anonymously' => $cron->anonymously,
            ];
            $max = max(strlen($time), $max);
        }
        foreach ($info as $cron) {
            $print->push('#');
            if ($cron['anonymously']) {
                $print->push($cron['time'])->textDim()->repeat(' ', $max + 1 - strlen($cron['time']));
            } else {
                $print->push($cron['time'])->textGreen()->repeat(' ', $max + 1 - strlen($cron['time']));
            }
            $print->push($cron['name'])->textYellow()->newLines();
        }

        $watchEnd = round(microtime(true) - $watchStart, 3) * 1000;
        $print->newLines()->push('done in ')
            ->push($watchEnd . ' ms')->textGreen()
            ->out();

        return 0;
    }

    /**
     * Simulates cron execution in the terminal.
     *
     * Continuously runs scheduled jobs every minute until interrupted by the user
     * (Ctrl+C). Shows real-time execution timestamps and job execution durations.
     *
     * @return void
     */
    public function work(): void
    {
        $print = new Style("\n");
        $print
            ->push('Simulate Cron in terminal (every minute)')->textBlue()
            ->newLines(2)
            ->push('type ctrl+c to stop')->textGreen()->underline()
            ->out();

        $terminalWidth = $this->getWidth(34, 50);

        while (true) {
            $clock = new Now();
            $print = new Style();
            $time  = $clock->year . '-' . $clock->month . '-' . $clock->day;

            $print
                ->push('Run cron at - ' . $time)->textDim()
                ->push(' ' . $clock->hour . ':' . $clock->minute . ':' . $clock->second);

            $watchStart = microtime(true);

            $this->getSchedule()->execute();

            $watchEnd = round(microtime(true) - $watchStart, 3) * 1000;
            $print
                ->repeat(' ', $terminalWidth - $print->length())
                ->push('-> ')->textDim()
                ->push($watchEnd . 'ms')->textYellow()
                ->out()
            ;

            // reset every 60 seconds
            sleep(60);
        }
    }

    /**
     * Returns the schedule instance with all registered jobs.
     *
     * @return Schedule The schedule containing registered cron jobs.
     */
    protected function getSchedule(): Schedule
    {
        $schedule = Scheduler::add(new Schedule());
        $this->scheduler($schedule);

        return $schedule;
    }

    /**
     * Registers cron jobs on the provided schedule.
     *
     * You can add multiple jobs and configure retry, just-in-time execution,
     * anonymity, and event names.
     *
     * @param Schedule $schedule The schedule to register jobs on.
     * @return void
     */
    public function scheduler(Schedule $schedule): void
    {
        $schedule->call(fn () => [
            'code' => 200,
        ])
        ->retry(2)
        ->justInTime()
        ->anonymously()
        ->eventName('cli-schedule');

        // others schedule
    }
}
