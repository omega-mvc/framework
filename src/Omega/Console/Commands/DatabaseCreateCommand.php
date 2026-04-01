<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'database:create',
    description: 'Create the specified database',
    options: [
        'database' => ['d', InputOption::VALUE_OPTIONAL, 'The database connection to use'],
        'force'    => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'yes'      => ['y', InputOption::VALUE_NONE, 'Do not ask for confirmation (Assume "yes")']
    ]
)]
final class DatabaseCreateCommand extends AbstractMigrationCommand
{
    public function __invoke(): int
    {
        return $this->databaseCreate();
    }
}
