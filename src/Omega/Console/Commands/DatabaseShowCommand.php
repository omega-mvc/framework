<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'database:show',
    description: 'Show database tables and sizes'
)]
final class DatabaseShowCommand extends AbstractMigrationCommand
{
    protected function configure(): void
    {
        $this->addDatabaseOption()
            ->addTableNameOption()
            ->addForceOption();
    }

    public function handle(): int
    {
        return $this->databaseShow();
    }
}
