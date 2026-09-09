<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Collection\Collection;
use Omega\Console\Attribute\AsCommand;
use Omega\Database\Schema\Query;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use DirectoryIterator;
use ReflectionException;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

use function is_dir;
use function is_string;
use function pathinfo;
use function rtrim;

#[AsCommand(
    name: 'migrate:fresh',
    description: 'Drop database and run migrations from scratch',
    options: [
        'force'    => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'dry-run'  => [null, InputOption::VALUE_NONE, 'Dump the SQL queries without executing'],
        'seed'     => [null, InputOption::VALUE_NONE, 'Seed the database after migrating'],
        'seed-namespace' => [null, InputOption::VALUE_OPTIONAL, 'The namespace of the seeder class'],
        'yes'      => ['y', InputOption::VALUE_NONE, 'Do not ask for confirmation (Assume "yes")'],
        'database' => ['d', InputOption::VALUE_OPTIONAL, 'The database connection to use']
    ]
)]
final class MigrateFreshCommand extends AbstractMigration
{
    /**
     * Drops and recreates the database, then runs all migrations from scratch.
     *
     * This method is typically used to reset the database to a clean state
     * and apply all migrations in order. It respects the `$silent` flag
     * to suppress prompts and output, and the `--dry-run` option to
     * preview SQL queries without executing them.
     *
     * @return int Exit code indicating the result of running migrations:
     *             0 on success, 2 if aborted due to environment or user confirmation failure,
     *             1 on general failure.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    public function __invoke(): int
    {
        if (!$this->runInDev('<fg=red;options=bold>Running migration/database in production?</> Continue?')) {
            return self::INVALID;
        }

        if ($this->getOption('dry-run')) {
            return $this->freshDryRun();
        }

        if (($drop = $this->call('db:wipe', ['--no-interact' => true])) > 0) {
            return $drop;
        }

        if (($create = $this->call('db:create', ['--no-interact' => true])) > 0) {
            return $create;
        }

        if (($init = $this->call('migrate:init')) > 0) {
            return $init;
        }

        // run migration
        $batch   = false;
        $migrate = $this->baseMigrate($batch)->sort();

        $this->io->title('Running migration');

        foreach ($migrate as $key => $val) {
            $schema = require $val['file_name'];
            $up     = $this->schemaQueries($schema, 'up');

            if ($this->getOption('dry-run')) {
                $up->each(function (Query $item): bool {
                    $this->io->writeln("<fg=gray>{$item->__toString()}</>");
                    $this->io->newLine();
                    return true;
                });
                continue;
            }

            try {
                $success = $up->every(fn (Query $item): bool => $item->execute());
            } catch (Throwable $th) {
                $this->io->newLine();
                $this->io->error($th->getMessage());
                $success = false;
            }

            $this->migrationOutputLine($key, $success);
        }

        $this->io->newLine();

        return $this->seed();
    }

    /**
     * Preview the SQL of every migration without touching the database.
     *
     * A fresh run re-executes every migration from scratch, so the preview
     * lists the `up` queries of all migration files currently on disk.
     *
     * @return int Always `0` on success.
     */
    private function freshDryRun(): int
    {
        $migrationsPath = $this->app->get('path.migrations');

        if (!is_string($migrationsPath)) {
            $this->io->error("The \"path.migrations\" binding must resolve to a string path.");
            return self::FAILURE;
        }

        $migrate = new Collection([]);
        $paths   = [$migrationsPath, ...static::$vendorPaths];

        foreach ($paths as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (new DirectoryIterator($dir) as $file) {
                if ($file->isDot() || $file->isDir()) {
                    continue;
                }

                $migrate->set(
                    pathinfo($file->getBasename(), PATHINFO_FILENAME),
                    rtrim($dir, '/') . '/' . $file->getFilename()
                );
            }
        }

        $this->io->title('Running migration');

        foreach ($migrate->sort() as $key => $filePath) {
            $schema = require $filePath;
            $up     = $this->schemaQueries($schema, 'up');

            $up->each(function (Query $item): bool {
                $this->io->writeln("<fg=gray>{$item->__toString()}</>");
                $this->io->newLine();
                return true;
            });
        }

        return self::SUCCESS;
    }
}
