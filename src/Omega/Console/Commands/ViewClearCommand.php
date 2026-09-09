<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Console\Traits\InteractWithFilesystemTrait;
use Symfony\Component\Console\Input\InputOption;

use function count;
use function is_file;
use function is_string;

#[AsCommand(
    name: 'view:clear',
    description: 'Clear all cached view files',
    options: [
        'prefix' => ['p', InputOption::VALUE_REQUIRED, 'File pattern to clear', '*.php']
    ]
)]
final class ViewClearCommand extends AbstractCommand
{
    use InteractWithFilesystemTrait;

    public function __invoke(): int
    {
        $compiledPath = $this->app->get('path.compiled_view_path');
        if (!is_string($compiledPath)) {
            $this->io->error('The "path.compiled_view_path" binding must resolve to a string path.');
            return self::FAILURE;
        }

        $prefix = $this->getOption('prefix');
        if (!is_string($prefix)) {
            $prefix = '*.php';
        }

        $files = $this->findFiles($compiledPath, $prefix);

        $count = 0;
        foreach ($files as $file) {
            if (is_file($file) && @unlink($file)) {
                $count++;
            }
        }

        $this->io->info("Cleared {$count} cached files.");
        return self::SUCCESS;
    }
}
