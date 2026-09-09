<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Console\Helpers\SpinnerProgress;
use Omega\Console\Traits\InteractWithFilesystemTrait;
use Omega\Text\Str;
use Omega\View\Templator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'view:cache',
    description: 'Compile all view templates',
    options: [
        'prefix' => ['p', InputOption::VALUE_REQUIRED, 'File pattern to stored in cache', '*.php']
    ]
)]
final class ViewCacheCommand extends AbstractCommand
{
    use InteractWithFilesystemTrait;

    public function __invoke(): int
    {
        $viewPath = $this->app->get('path.view');

        $files = $this->findFiles($viewPath, $this->getOption('prefix'));

        if (empty($files)) {
            $this->io->warning('No view files found.');
            return self::SUCCESS;
        }

        $templator = $this->app[Templator::class];
        $progressBar = $this->io->progressBar(count($files), 'Compiling views...');
        $progressBar->start();

        foreach ($files as $file) {
            $relativeName = Str::replace($file, $viewPath, '');
            $templator->compile($relativeName);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->newLine(2);
        $this->io->info("View cache built successfully.");

        return self::SUCCESS;
    }
}
