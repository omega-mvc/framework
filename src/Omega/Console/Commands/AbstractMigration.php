<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Console\Commands;

use DirectoryIterator;
use Exception;
use Omega\Collection\Collection;
use Omega\Console\AbstractCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Facades\DB;
use Omega\Database\Schema\Query;
use Omega\Database\Schema\SchemaConnection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Throwable;

use function is_dir;
use function max;
use function min;
use function pathinfo;
use function rtrim;
use function str_contains;
use function str_repeat;
use function strlen;

/**
 * Base class for database and migration commands.
 *
 * Provides the shared building blocks for writing, rolling back, resetting,
 * and inspecting database migrations, plus the SQL queries used to run them.
 *
 * @category  Omega
 * @package   Console
 * @subpackage Commands
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
abstract class AbstractMigration extends AbstractCommand
{
    /**
     * Optional vendor migration paths registered by subclasses.
     *
     * @var array<int, string>
     */
    protected static array $vendorPaths = [];

    /**
     * Retrieve the target database name for migration operations.
     *
     * Returns the database name specified via the `--database` option, or the
     * default database name from the application's schema connection.
     *
     * @return string The name of the database to be used for migration commands.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the schema connection is not registered.
     * @throws ReflectionException Thrown when a class or interface cannot be reflected.
     */
    protected function getDatabaseName(): string
    {
        $database = $this->getOption('database');

        return $database ?? $this->app->get(SchemaConnection::class)->getDatabase();
    }

    /**
     * Determine whether the given database exists on the server.
     *
     * Supports MySQL, MariaDB, and PostgreSQL. SQLite databases are always
     * considered present because the connection targets a file path.
     *
     * @param string $database Database name.
     * @return bool True if the database exists.
     */
    protected function databaseExists(string $database): bool
    {
        $driver = $this->app->get('dsn.sql')['driver'] ?? 'mysql';

        $sql = match ($driver) {
            'pgsql'  => 'SELECT COUNT(*) AS total FROM pg_database WHERE datname = ?',
            'sqlite' => null,
            default  => 'SELECT COUNT(*) AS total FROM information_schema.schemata WHERE schema_name = ?',
        };

        if ($sql === null) {
            return true;
        }

        $row = $this->app->get(SchemaConnection::class)
            ->query($sql)
            ->bind(1, $database)
            ->single();

        return is_array($row) && (int) $row['total'] > 0;
    }

    /**
     * Determine whether migration commands are allowed to run.
     *
     * The command may run when the application is in development mode, when
     * the `--force` option is provided, or when the user confirms the run.
     *
     * @param string|null $message Optional confirmation message shown in production.
     * @return bool True when running is allowed, false otherwise.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     */
    protected function runInDev(?string $message = null): bool
    {
        if ($this->app->isDev() || $this->getOption('force')) {
            return true;
        }

        if ($message === null) {
            return false;
        }

        return $this->io->confirm($message, false);
    }

    /**
     * Retrieve the list of migrations to be executed.
     *
     * Collects migration files from the default migration path and any
     * registered vendor paths, compares them with the migration table, and
     * determines which migrations need to be run for the given batch.
     *
     * @param false|int $batch Optional batch number to limit the migrations.
     * @param bool $register Whether to insert new migrations into the migration table.
     * @return Collection<string, array<string, string|int>> Migration names mapped
     *         to arrays containing `file_name` and `batch`.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when a class or interface cannot be reflected.
     */
    protected function baseMigrate(false|int &$batch = false, bool $register = true): Collection
    {
        $migrationBatch = $this->getMigrationTable();

        $higher = $migrationBatch->length() > 0
            ? $migrationBatch->max() + 1
            : 0;

        $batch = false === $batch ? $higher : $batch;

        $paths   = [$this->app->get('path.migrations'), ...static::$vendorPaths];
        $migrate = new Collection([]);

        foreach ($paths as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (new DirectoryIterator($dir) as $file) {
                if ($file->isDot() || $file->isDir()) {
                    continue;
                }

                $migrationName = pathinfo($file->getBasename(), PATHINFO_FILENAME);
                $hasMigration  = $migrationBatch->has($migrationName);

                $filePath = rtrim($dir, '/') . '/' . $file->getFilename();

                if (false === $hasMigration) {
                    $migrate->set($migrationName, [
                        'file_name' => $filePath,
                        'batch'     => $higher,
                    ]);

                    if ($register) {
                        $this->insertMigrationTable([
                            'migration' => $migrationName,
                            'batch'     => $higher,
                        ]);
                    }

                    continue;
                }

                if ($migrationBatch->get($migrationName) <= $batch) {
                    $migrate->set($migrationName, [
                        'file_name' => $filePath,
                        'batch'     => $migrationBatch->get($migrationName),
                    ]);
                }
            }
        }

        return $migrate;
    }

    /**
     * Execute all pending migrations for the current batch.
     *
     * Retrieves pending migration files and runs their `up` scripts. If the
     * `--dry-run` option is passed, the SQL queries are only displayed. When
     * `$silent` is true, the production confirmation prompt is skipped.
     *
     * @param bool $silent When true, skips the production confirmation prompt.
     * @return int Exit code: 0 on success, 2 when aborted, 1 on failure.
     * @throws ContainerExceptionInterface Thrown on general container errors.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     * @throws ExceptionInterface
     */
    protected function migration(bool $silent = false): int
    {
        if (
            false === $silent
            && false === $this->runInDev('<fg=red;options=bold>Running migration/database in production?</> Continue?')
        ) {
            return self::INVALID;
        }

        $batch = false;
        $migrate = $this->baseMigrate($batch);

        $migrate = $migrate
            ->filter(static fn (array $value): bool => (int) $value['batch'] === (int) $batch)
            ->sort();

        if ($migrate->isEmpty()) {
            $this->io->info('Nothing to migrate.');
            return self::SUCCESS;
        }

        $this->io->title('Running migrations');

        foreach ($migrate as $key => $val) {
            $schema = require $val['file_name'];
            $up = new Collection($schema['up'] ?? []);

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
                return self::FAILURE;
            }

            $this->migrationOutputLine($key, $success);
        }

        $this->io->newLine();

        return $this->seed();
    }

    /**
     * Execute seeders after migrations based on the provided options.
     *
     * @return int Exit code: 0 when no seeding is performed or on success,
     *              otherwise the exit code returned by the seeder command.
     */
    protected function seed(): int
    {
        if ($this->getOption('dry-run')) {
            return self::SUCCESS;
        }

        $shouldSeed = $this->getOption('seed');

        if (!$shouldSeed) {
            return self::SUCCESS;
        }

        $parameters = [];

        $namespace = $this->getOption('seed-namespace');
        if ($namespace) {
            $parameters['--name-space'] = $namespace;
        }

        try {
            return $this->call('db:seed', $parameters);
        } catch (Throwable $e) {
            $this->io->error('Seeding failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Retrieve the list of executed migrations mapped to their batch numbers.
     *
     * @return Collection<string, int> A collection mapping migration names to their batch numbers.
     */
    protected function getMigrationTable(): Collection
    {
        /** @var Collection<string, int> $pair */
        $pair = DB::table('migration')
            ->select()
            ->get()
            ->assocBy(static fn (array $item): array => [$item['migration'] => (int) $item['batch']]);

        return $pair;
    }

    /**
     * Roll back executed migrations based on a batch number and a limit.
     *
     * @param false|int $batch The batch number to roll back, or false to determine it automatically.
     * @param int $take The number of batches to roll back starting from the given batch.
     * @return int Exit code: 0 on success, 1 when at least one migration fails.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when a class or interface cannot be reflected.
     */
    protected function rollbacks(false|int $batch, int $take): int
    {
        $migrate = false === $batch
            ? $this->baseMigrate($batch, false)
            : $this->baseMigrate($batch, false)
                ->filter(static fn (array $value): bool => $value['batch'] >= $batch - $take);

        $failed = false;

        foreach ($migrate->sortDesc() as $key => $val) {
            $schema = require $val['file_name'];
            $down = new Collection($schema['down'] ?? []);

            if ($this->getOption('dry-run')) {
                $down->each(function (Query $item): bool {
                    $this->io->writeln("<fg=gray>{$item->__toString()}</>");
                    $this->io->newLine(2);
                    return true;
                });
                continue;
            }

            try {
                $success = $down->every(fn (Query $item): bool => $item->execute());

                if ($success) {
                    $success = $this->deleteMigrationTable((int) $val['batch']);
                }
            } catch (Throwable $th) {
                if (str_contains($th->getMessage(), 'Base table or view not found')) {
                    $success = true;
                } else {
                    $success = false;
                    $this->io->error($th->getMessage());
                }
            }

            if (!$success) {
                $failed = true;
            }

            $this->migrationOutputLine($key, $success);
        }

        $this->io->newLine();

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Determine the width available for migration progress output.
     *
     * @return int The usable width for aligned migration output.
     */
    protected function outputWidth(): int
    {
        return min($this->terminal->getWidth() - 20, 60);
    }

    /**
     * Write an aligned migration progress line with a DONE or FAIL tag.
     *
     * The migration name is rendered at the start of the line, followed by
     * gray dots up to the output width and a colored success or failure tag.
     *
     * @param string $key The migration name to display.
     * @param bool $success Whether the migration executed successfully.
     * @return void
     */
    protected function migrationOutputLine(string $key, bool $success): void
    {
        $this->io->write("<fg=gray>{$key}</>");

        $dotCount = max(0, $this->outputWidth() - strlen($key));
        if ($dotCount > 0) {
            $this->io->write('<fg=gray>' . str_repeat('.', $dotCount) . '</>');
        }

        $this->io->writeln($success ? ' <info>DONE</info>' : ' <error>FAIL</error>');
    }

    /**
     * Insert a migration record into the migration table.
     *
     * @param array<string, string|int> $migration The migration name and its batch number.
     * @return bool Returns true on successful insertion, false otherwise.
     */
    private function insertMigrationTable(array $migration): bool
    {
        return DB::table('migration')
            ->insert()
            ->values($migration)
            ->execute()
            ;
    }

    /**
     * Delete migration records for the specified batch number.
     *
     * @param int $batchNumber The batch number whose migrations should be removed.
     * @return bool Returns true on successful deletion, false otherwise.
     */
    private function deleteMigrationTable(int $batchNumber): bool
    {
        return DB::table('migration')
            ->delete()
            ->equal('batch', $batchNumber)
            ->execute()
            ;
    }
}