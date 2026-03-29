<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Omega\Cron\Schedule;
use Omega\Support\Facades\Schedule as Scheduler;

#[AsCommand(
    name: 'cron:list',
    description: 'List all scheduled cron jobs'
)]
final class CronListCommand extends AbstractCommand
{
    protected function handle(): int
    {
        $schedule = Scheduler::add(new Schedule());
        $pools = $schedule->getPools();

        if (empty($pools)) {
            $this->warn('No scheduled jobs found.');
            return self::SUCCESS;
        }

        $table = new Table($this->io);
        $table->setHeaders(['Schedule', 'Event Name', 'Anonymous']);

        foreach ($pools as $cron) {
            $table->addRow([
                "<info>{$cron->timeName}</info>",
                "<comment>{$cron->eventName}</comment>",
                $cron->anonymously ? '<fg=gray>Yes</>' : 'No'
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }
}
