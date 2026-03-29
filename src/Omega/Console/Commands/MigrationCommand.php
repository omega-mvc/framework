<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Console\AbstractCommand;
use Omega\Support\Bootstrap\ConfigProviders;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(
    name: 'config:cache',
    description: 'Create a cache file for faster configuration loading'
)]
final class MigrationCommand extends BaseMigrationCommand
{
    protected function configure(): void
    {
        $this->addForceOption()->addDryRunOption()->addSeedOption();
    }

    public function handle(): int
    {
        return $this->migration();
    }
}
