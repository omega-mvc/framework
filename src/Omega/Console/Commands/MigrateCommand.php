<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;

#[AsCommand(
    name: 'migrate',
    description: 'Run migration (up).'
)]
final class MigrateCommand extends AbstractMigrationCommand
{
    protected function configure(): void
    {
        $this->addForceOption()->addDryRunOption()->addSeedOption();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     */
    public function handle(): int
    {
        return $this->migration();
    }
}
