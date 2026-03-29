<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use function Omega\Support\app;
use function Omega\Support\get_path;

#[AsCommand(
    name: 'up',
    description: 'Bring the application out of maintenance mode'
)]
final class MaintenanceUpCommand extends AbstractCommand
{
    protected function handle(): int
    {
        if (!app()->isDownMaintenanceMode()) {
            $this->warn('Application is already live.');
            return self::FAILURE;
        }

        $maintenanceFile = get_path('path.storage') . 'app/maintenance.php';

        if (file_exists($maintenanceFile) && !@unlink($maintenanceFile)) {
            $this->error('Failed to remove the maintenance file.');
            $this->io->note("Please remove it manually at: $maintenanceFile");
            return self::FAILURE;
        }

        $this->success('Application is now live.');

        return self::SUCCESS;
    }
}
