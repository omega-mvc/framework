<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'migrate:reset',
    description: 'Rolling back all migrations (down)'
)]
final class MigrateResetCommand extends AbstractMigrationCommand
{
    protected function configure(): void
    {
        $this->addDryRunOption()->addForceOption();
    }

    public function __invoke(): int
    {
        return $this->reset();
    }
}
