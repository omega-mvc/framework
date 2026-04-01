<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'migrate:fresh',
    description: 'Drop database and run migrations from scratch'
)]
final class MigrateFreshCommand extends AbstractMigrationCommand
{
    protected function configure(): void
    {
        $this->addForceOption()
            ->addYesOption()
            ->addDryRunOption()
            ->addDatabaseOption()
            ->addSeedOption();
    }

    public function handle(): int
    {
        return $this->fresh();
    }
}
