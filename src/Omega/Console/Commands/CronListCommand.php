<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Cron\Schedule;
use Omega\Support\Facades\Schedule as Scheduler;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'cron:list',
    description: 'List all scheduled cron jobs'
)]
final class CronListCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        $schedule = Scheduler::add(new Schedule());
        $pools = $schedule->getPools();

        if (empty($pools)) {
            $this->io->warning('No scheduled jobs found.');
            return self::SUCCESS;
        }

        $table = new Table($this->output);
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
