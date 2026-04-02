<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'view:clear',
    description: 'Clear all cached view files',
    options: [
        'prefix' => ['p', InputOption::VALUE_REQUIRED, 'File pattern to clear', '*.php']
    ]
)]
final class ViewClearCommand extends AbstractCommand
{
    use ViewCommandFilesTrait;

    public function __invoke(): int
    {
        $compiledPath = $this->app->get('path.compiled_view_path');
        $this->io->warning("Clearing view cache...");

        // Usiamo lo stesso metodo ereditato
        $files = $this->findFiles($compiledPath, $this->getOption('prefix'));

        $count = 0;
        foreach ($files as $file) {
            if (is_file($file) && @unlink($file)) {
                $count++;
            }
        }

        $this->io->success("Cleared {$count} cached files.");
        return self::SUCCESS;
    }
}
