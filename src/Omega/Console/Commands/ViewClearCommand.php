<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'view:clear', description: 'Clear all cached view files')]
final class ViewClearCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addOption('prefix', 'p', InputOption::VALUE_REQUIRED, 'File pattern', '*.php');
    }

    protected function handle(): int
    {
        $compiledPath = $this->app->get('path.compiled_view_path');
        $this->warn("Clearing view cache...");

        // Usiamo lo stesso metodo ereditato
        $files = $this->findFiles($compiledPath, $this->option('prefix'));

        $count = 0;
        foreach ($files as $file) {
            if (is_file($file) && @unlink($file)) {
                $count++;
            }
        }

        $this->success("Cleared {$count} cached files.");
        return self::SUCCESS;
    }
}
