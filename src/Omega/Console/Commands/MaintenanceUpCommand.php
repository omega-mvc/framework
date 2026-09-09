<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'up',
    description: 'Bring the application out of maintenance mode'
)]
final class MaintenanceUpCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        if (!$this->app->isDownMaintenanceMode()) {
            $this->io->warning('Application is already live.');
            return self::FAILURE;
        }

        $storagePath = $this->app->get('path.storage') . 'app/';

        foreach (['maintenance.php', 'down'] as $file) {
            $path = $storagePath . $file;

            if (file_exists($path) && !@unlink($path)) {
                $this->io->error("Failed to remove the maintenance file '$path'.");
                $this->io->note("Please remove it manually at: $path");
                return self::FAILURE;
            }
        }

        $this->io->info('Application is now live.');

        return self::SUCCESS;
    }
}