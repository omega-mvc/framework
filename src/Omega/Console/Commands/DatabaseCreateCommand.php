<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Exception;
use Omega\Console\Attribute\AsCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Support\Facades\Schema;
use PDOException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
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
    /**
     * Create the target database and initialize the migration table if needed.
     *
     * @param bool $silent If `true`, suppresses confirmation prompts and environment checks.
     * @return int Exit code indicating the result of the operation:
     *             0 on success, 1 on failure, 2 if aborted due to environment or user confirmation.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown if reading input from STDIN fails during confirmation prompts.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function __invoke(): int
    {
        $silent  = $this->getOption('silent');
        $dbName  = $this->getDatabaseName();
        $message = "Do you want to create database `{$dbName}`?";

        // Controllo ambiente e conferma
        if (false === $silent && (!$this->runInDev() || !$this->confirmation($message))) {
            return self::INVALID;
        }

        // Output informativo
        $this->io->writeln("<info>Creating database `{$dbName}`...</info>");

        try {
            $success = Schema::create()->database($dbName)->execute();
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'database exists')) {
                $this->io->error("Database `{$dbName}` already exists.");
                return self::FAILURE;
            }
            throw $e;
        }


        $success = Schema::create()->database($dbName)->ifNotExists()->execute();

        if ($success) {
            $this->io->success("Successfully created database `{$dbName}`");

            $this->call('migrate:init');

            return self::SUCCESS;
        }

        $this->io->error("Cannot create database `{$dbName}`");

        return self::FAILURE;
    }
}
