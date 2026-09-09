<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Cron\Schedule;
use Omega\Cron\Facade\Schedule as Scheduler;

use function microtime;
use function round;

#[AsCommand(
    name: 'cron:run',
    description: 'Run all scheduled cron jobs'
)]
final class CronCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(): int
    {
        $start = microtime(true);

        $this->getSchedule()->execute();

        $time = round((microtime(true) - $start) * 1000, 2);
        $this->io->info("Cron jobs executed successfully in {$time}ms.");

        return self::SUCCESS;
    }

    /**
     * Returns the schedule instance with all registered jobs.
     *
     * Jobs are registered by the application via `routes/schedule.php`, which is
     * loaded into the `schedule` container binding by `RouteServiceProvider`.
     *
     * @return Schedule The schedule containing registered cron jobs.
     */
    protected function getSchedule(): Schedule
    {
        return Scheduler::add(new Schedule());
    }
}
