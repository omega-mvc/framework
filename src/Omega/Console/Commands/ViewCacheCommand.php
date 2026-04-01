<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Text\Str;
use Omega\View\Templator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'view:cache', description: 'Compile all view templates')]
final class ViewCacheCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addOption('prefix', 'p', InputOption::VALUE_REQUIRED, 'File pattern', '*.php');
    }

    public function __invoke(): int
    {
        $this->io->info('Building view compiler cache...');
        $viewPath = $this->app->get('path.view');

        // Usiamo il metodo ereditato da AbstractCommand
        $files = $this->findFiles($viewPath, $this->getOption('prefix'));

        if (empty($files)) {
            $this->io->warning('No view files found.');
            return self::SUCCESS;
        }

        $templator = $this->app[Templator::class];
        $progressBar = new ProgressBar($this->output, count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $relativeName = Str::replace($file, $viewPath, '');
            $templator->compile($relativeName);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->newLine();
        $this->io->success("View cache built successfully.");

        return self::SUCCESS;
    }
}
