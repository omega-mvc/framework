<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;

use function file_exists;
use function unlink;

#[AsCommand(
    name: 'route:clear',
    description: 'Remove the route cache file'
)]
class RouteClearCommand extends AbstractCommand
{
    protected function handle(): int
    {
        $io = $this->io;
        $cachePath = $this->app->getApplicationCachePath() . 'route.php';

        if (file_exists($cachePath)) {
            if (@unlink($cachePath)) {
                $io->success('Route cache cleared successfully.');
                return 0;
            }

            $io->error('Unable to delete the route cache file.');
            return 1;
        }

        $io->info('No route cache file found.');
        return 0;
    }
}
