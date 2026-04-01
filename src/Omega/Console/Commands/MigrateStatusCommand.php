<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'migrate:status',
    description: 'Show migration status.'
)]
final class MigrateStatusCommand extends AbstractMigrationCommand
{
    protected function configure(): void
    {
    }

    /**
     * Crea il database target e inizializza il sistema delle migrazioni.
     */
    public function handle(): int
    {
        return $this->status();
    }
}
