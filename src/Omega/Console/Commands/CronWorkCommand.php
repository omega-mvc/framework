<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Omega\Cron\Schedule;
use Omega\Support\Facades\Schedule as Scheduler;
use Omega\Time\Now;

#[AsCommand(
    name: 'cron:work',
    description: 'Simulate the cron scheduler in the terminal'
)]
final class CronWorkCommand extends AbstractCommand
{
    protected function handle(): int
    {
        $this->io->title('Omega Cron Worker');
        $this->info('Watching and executing scheduled jobs every minute...');
        $this->io->note('Press CTRL+C to stop the worker.');

        $schedule = Scheduler::add(new Schedule());

        while (true) {
            $now = new Now();
            $timestamp = sprintf(
                '%s-%s-%s %02d:%02d:%02d',
                $now->getYear(), $now->getMonth(), $now->getDay(),
                $now->getHour(), $now->getMinute(), $now->getSecond()
            );

            $this->io->write("<fg=gray>[{$timestamp}]</> Running scheduled tasks... ");

            $start = microtime(true);
            $schedule->execute();
            $executionTime = round((microtime(true) - $start) * 1000, 2);

            $this->io->writeln("<info>Done!</info> ({$executionTime}ms)");

            // Aspetta fino al prossimo minuto (o usa un ciclo di 60s)
            sleep(60);
        }
    }
}
