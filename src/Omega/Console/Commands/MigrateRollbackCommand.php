<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'migrate:rollback',
    description: 'Rolling back last migrations (down)'
)]
final class MigrateRollbackCommand extends AbstractMigrationCommand
{
    protected function configure(): void
    {
        $this->addSeedOption()->addDryRunOption()->addForceOption()->addBatchOption()->addTakeOption();
    }

    public function __invoke(): int
    {
        return $this->rollback();
    }
}
