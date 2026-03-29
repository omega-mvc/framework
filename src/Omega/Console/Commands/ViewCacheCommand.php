<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Omega\View\Templator;
use Omega\Text\Str;


#[AsCommand(name: 'view:cache', description: 'Compile all view templates')]
final class ViewCacheCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addOption('prefix', 'p', InputOption::VALUE_REQUIRED, 'File pattern', '*.php');
    }

    protected function handle(): int
    {
        $this->info('Building view compiler cache...');
        $viewPath = $this->app->get('path.view');

        // Usiamo il metodo ereditato da AbstractCommand
        $files = $this->findFiles($viewPath, $this->option('prefix'));

        if (empty($files)) {
            $this->warn('No view files found.');
            return self::SUCCESS;
        }

        $templator = $this->app[Templator::class];
        $progressBar = new ProgressBar($this->io, count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $relativeName = Str::replace($file, $viewPath, '');
            $templator->compile($relativeName);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->newLine();
        $this->success("View cache built successfully.");

        return self::SUCCESS;
    }
}
