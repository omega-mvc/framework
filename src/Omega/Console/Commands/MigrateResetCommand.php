<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Exception;
use Omega\Console\Attribute\AsCommand;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'migrate:reset',
    description: 'Rolling back all migrations (down)',
    options: [
        'force'    => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'dry-run'  => [null, InputOption::VALUE_NONE, 'Dump the SQL queries without executing'],
        'silent'   => [null, InputOption::VALUE_NONE, 'Skip the environment check and user prompts']
    ]
)]
final class MigrateResetCommand extends AbstractMigration
{
    /**
     * Roll back all executed migrations.
     *
     * @return int Exit code indicating the result of the rollback operation:
     *             0 on success, 2 if aborted due to environment restrictions or confirmation failure.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     */
    public function __invoke(): int
    {
        $silent = $this->getOption('silent');

        if (false === $this->runInDev() && false === $silent) {
            return self::INVALID;
        }

        $this->io->migrate('Rolling back all migrations');

        return $this->rollbacks(false, 0);
    }
}
