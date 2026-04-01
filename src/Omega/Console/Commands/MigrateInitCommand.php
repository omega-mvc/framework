<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'migrate:init',
    description: 'Initialize the migration table in the database'
)]
final class MigrateInitCommand extends AbstractMigrationCommand
{
    /**
     * Configurazione del comando.
     */
    protected function configure(): void
    {
        // Spesso utile anche qui per bypassare controlli se necessario
        $this->addForceOption();
    }

    /**
     * Initialize the migration system by creating the migration table if it does not exist.
     *
     * @return int Exit code indicating the result:
     *             0 if the migration table already exists or is successfully created,
     *             1 if the migration table creation fails.
     */
    public function handle(): int
    {
        return $this->initializeMigration();
    }
}
