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

        $maintenanceFile = $this->app->get('path.storage') . 'app/maintenance.php';

        if (file_exists($maintenanceFile) && !@unlink($maintenanceFile)) {
            $this->io->error('Failed to remove the maintenance file.');
            $this->io->note("Please remove it manually at: $maintenanceFile");
            return self::FAILURE;
        }

        $this->io->success('Application is now live.');

        return self::SUCCESS;
    }
}
