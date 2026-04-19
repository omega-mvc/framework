<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use function file_exists;
use function unlink;

#[AsCommand(
    name: 'route:clear',
    description: 'Remove the route cache file'
)]
class RouteClearCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        $cachePath = $this->app->getApplicationCachePath() . 'route.php';

        if (file_exists($cachePath)) {
            if (@unlink($cachePath)) {
                $this->io->info('Route cache cleared successfully.');
                return self::SUCCESS;
            }

            $this->io->error('Unable to delete the route cache file.');
            return self::FAILURE;
        }

        $this->io->warning('No route cache file found.');
        return self::INVALID;
    }
}
