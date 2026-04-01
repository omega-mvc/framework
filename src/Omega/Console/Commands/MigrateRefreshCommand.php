<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'migrate:refresh',
    description: 'Rolling back and run migration all'
)]
final class MigrateRefreshCommand extends AbstractMigrationCommand
{
    protected function configure(): void
    {
        $this->addSeedOption()->addDryRunOption()->addForceOption();
    }

    public function __invoke(): int
    {
        return $this->refresh();
    }
}
