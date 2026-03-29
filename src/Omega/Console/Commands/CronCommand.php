<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Omega\Cron\Schedule;
use Omega\Support\Facades\Schedule as Scheduler;

#[AsCommand(
    name: 'cron:run',
    description: 'Run all scheduled cron jobs'
)]
final class CronCommand extends AbstractCommand
{
    protected function handle(): int
    {
        $start = microtime(true);

        $this->getSchedule()->execute();

        $time = round((microtime(true) - $start) * 1000, 2);
        $this->success("Cron jobs executed successfully in {$time}ms.");

        return self::SUCCESS;
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
