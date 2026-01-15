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
use Omega\Console\Prompt;
use Omega\Console\Style\Style;
use Omega\Console\Traits\PrintHelpTrait;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\Table\Create;
use Omega\Support\Facades\DB;
use Omega\Support\Facades\PDO;
use Omega\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Throwable;

use function Omega\Console\error;
use function Omega\Console\info;
use function Omega\Console\style;
use function Omega\Console\success;
use function Omega\Console\warn;

use const PATHINFO_FILENAME;

/**
 * Handles all database migration commands for the application.
 *
 * This class provides functionality to run, rollback, refresh, and manage
 * migrations, as well as to create, drop, and inspect databases. It also
 * integrates seeding and allows adding custom vendor migration paths.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @property ?int        $take
 * @property ?int        $batch
 * @property bool        $force
 * @property string|bool $seed
 */
class MigrationCommand extends AbstractCommand
{
    use PrintHelpTrait;

    /**
     * List of registered vendor migration paths.
     *
     * These paths will be scanned in addition to the default migration
     * directory when running migration commands.
     *
     * @var string[]
     */
    public static array $vendorPaths = [];

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'migrate',
            'fn'      => [self::class, 'main'],
        ], [
            'pattern' => 'migrate:fresh',
            'fn'      => [self::class, 'fresh'],
        ], [
            'pattern' => 'migrate:reset',
            'fn'      => [self::class, 'reset'],
        ], [
            'pattern' => 'migrate:refresh',
            'fn'      => [self::class, 'refresh'],
        ], [
            'pattern' => 'migrate:rollback',
            'fn'      => [self::class, 'rollback'],
        ], [
            'pattern' => ['database:create', 'db:create'],
            'fn'      => [self::class, 'databaseCreate'],
        ], [
            'pattern' => ['database:drop', 'db:drop'],
            'fn'      => [self::class, 'databaseDrop'],
        ], [
            'pattern' => ['database:show', 'db:show'],
            'fn'      => [self::class, 'databaseShow'],
        ], [
            'pattern' => 'migrate:status',
            'fn'      => [self::class, 'status'],
        ], [
            'pattern' => 'migrate:init',
            'fn'      => [self::class, 'initializeMigration'],
        ],
    ];

    /**
     * Returns a description of the command, its options, and their relations.
     *
     * This is used to generate help output for users.
     *
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp(): array
    {
        return [
            'commands'  => [
                'migrate'                  => 'Run migration (up)',
                'migrate:fresh'            => 'Drop database and run migrations',
                'migrate:reset'            => 'Rolling back all migrations (down)',
                'migrate:refresh'          => 'Rolling back and run migration all',
                'migrate:rollback'         => 'Rolling back last migrations (down)',
                'migrate:init'             => 'Initialize migration table',
                'migrate:status'           => 'Show migration status.',
                'database:create'          => 'Create database',
                'database:drop'            => 'Drop database',
                'database:show'            => 'Show database table',
            ],
            'options'   => [
                '--take'              => 'Limit of migrations to be run.',
                '--batch'             => 'Batch migration execution.',
                '--dry-run'           => 'Execute migration but only get query output.',
                '--force'             => 'Force running migration/database query in production.',
                '--seed'              => 'Run seeder after migration.',
                '--seed-namespace'    => 'Run seeder after migration using class namespace.',
                '--yes'               => 'Accept it without having it ask any questions',
                '--database'          => 'Target database to use.',
            ],
            'relation'  => [
                'migrate'                   => ['--seed', '--dry-run', '--force'],
                'migrate:fresh'             => ['--seed', '--dry-run', '--force'],
                'migrate:reset'             => ['--dry-run', '--force'],
                'migrate:refresh'           => ['--seed', '--dry-run', '--force'],
                'migrate:rollback'          => ['--batch', '--take', '--dry-run', '--force'],
                'database:create'           => ['--database', '--force'],
                'database:drop'             => ['--database', '--force'],
                'database:show'             => ['--database', '--force'],
            ],
        ];
    }

    /**
     * Retrieve the target database name for migration operations.
     *
     * This method returns the database name specified via the command-line option
     * `--database`. If no option is provided, it retrieves the default database
     * name from the application's schema connection.
     *
     * @return string The name of the database to be used for migration commands.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    private function DbName(): string
    {
        return $this->option('database', app()->get(SchemaConnection::class)->getDatabase());
    }

    /**
     * Determine whether migration commands are running in a development environment.
     *
     * This method checks if the application is in development mode (`app()->isDev()`)
     * or if the `--force` option is provided. If not, it prompts the user to confirm
     * running migrations in production.
     *
     * @return bool Returns `true` if running in a development environment or if the user
     *              confirms running in production; otherwise, `false`.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    private function runInDev(): bool
    {
        if (app()->isDev() || $this->force) {
            return true;
        }

        /* @var bool */
        return new Prompt(style('Running migration/database in production?')->textRed(), [
            'yes' => fn () => true,
            'no'  => fn () => false,
        ], 'no')
            ->selection([
                style('yes')->textDim(),
                ' no',
            ])
            ->option();
    }

    /**
     * Prompt the user for confirmation with a yes/no question.
     *
     * This method displays a prompt message to the user and waits for a response.
     * If the `--yes` option is provided, the method automatically returns `true`
     * without asking.
     *
     * @param Style|string $message The message to display in the prompt. Can be a string or a styled message.
     * @return bool Returns `true` if the user confirms, otherwise `false`.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     */
    private function confirmation(Style|string $message): bool
    {
        if ($this->option('yes', false)) {
            return true;
        }

        /* @var bool */
        return new Prompt($message, [
            'yes' => fn () => true,
            'no'  => fn () => false,
        ], 'no')
        ->selection([
            style('yes')->textDim(),
            ' no',
        ])
        ->option();
    }

    /**
     * Retrieve the list of migrations to be executed.
     *
     * This method collects migration files from the default migration path and any
     * registered vendor paths, compares them with the migration table, and determines
     * which migrations need to be run for the given batch.
     *
     * @param false|int $batch Optional batch number to limit the migrations. If `false`,
     *                         the next batch number will be used automatically.
     * @return Collection<string, array<string, string>> Returns a collection mapping
     *         migration names to arrays containing `file_name` and `batch`.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function baseMigrate(false|int &$batch = false): Collection
    {
        $migrationBatch = $this->getMigrationTable();
        $higher         = $migrationBatch->length() > 0
            ? $migrationBatch->max() + 1
            : 0;
        $batch = false === $batch ? $higher : $batch;

        $paths   = [get_path('path.migration'), ...static::$vendorPaths];
        $migrate = new Collection([]);
        foreach ($paths as $dir) {
            foreach (new DirectoryIterator($dir) as $file) {
                if ($file->isDot() | $file->isDir()) {
                    continue;
                }

                $migrationName = pathinfo($file->getBasename(), PATHINFO_FILENAME);
                $hasMigration  = $migrationBatch->has($migrationName);

                if (!$batch && $hasMigration) {
                    if ($migrationBatch->get($migrationName) <= $higher - 1) {
                        $migrate->set($migrationName, [
                            'file_name' => $dir . $file->getFilename(),
                            'batch'     => $migrationBatch->get($migrationName),
                        ]);
                        continue;
                    }
                }

                if (false === $hasMigration) {
                    $migrate->set($migrationName, [
                        'file_name' => $dir . $file->getFilename(),
                        'batch'     => $higher,
                    ]);
                    $this->insertMigrationTable([
                        'migration' => $migrationName,
                        'batch'     => $higher,
                    ]);
                    continue;
                }

                if ($migrationBatch->get($migrationName) <= $batch) {
                    $migrate->set($migrationName, [
                        'file_name' => $dir . $file->getFilename(),
                        'batch'     => $migrationBatch->get($migrationName),
                    ]);
                }
            }
        }

        return $migrate;
    }

    /**
     * {@inheritdoc}
     *
     * @return int Exit code indicating the result of running migrations:
     *             0 on success, 2 if aborted due to environment or user confirmation failure,
     *             1 on general failure.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception Thrown if an unexpected error occurs during migration execution.
     */
    public function main(): int
    {
        return $this->migration();
    }

    /**
     * Execute all pending migrations for the current batch.
     *
     * This method retrieves migration files, compares them with the migration table,
     * and runs their `up` scripts. If the `--dry-run` option is provided, the SQL
     * queries will only be displayed without executing them. Execution can be
     * suppressed using the `$silent` flag.
     *
     * @param bool $silent If `true`, suppresses prompts and outputs; otherwise prompts may be shown.
     * @return int Exit code indicating the result of running migrations:
     *             0 on success, 2 if aborted due to environment or user confirmation failure,
     *             1 on general failure.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception Thrown if an unexpected error occurs during migration execution.
     */
    public function migration(bool $silent = false): int
    {
        if (false === $this->runInDev() && false === $silent) {
            return 2;
        }

        $print   = new Style();
        $width   = $this->getWidth(40, 60);
        $batch   = false;
        $migrate = $this->baseMigrate($batch);
        $migrate
            ->filter(static fn ($value): bool => $value['batch'] == $batch)
            ->sort();

        /** @noinspection DuplicatedCode */
        $print->tap(info('Running migration'));

        foreach ($migrate as $key => $val) {
            $schema = require_once $val['file_name'];
            $up     = new Collection($schema['up'] ?? []);

            if ($this->option('dry-run')) {
                $up->each(function ($item) use ($print) {
                    $print->push($item->__toString())->textDim()->newLines(2);

                    return true;
                });
                continue;
            }

            $print->push($key)->textDim();
            $print->repeat('.', $width - strlen($key))->textDim();

            try {
                $success = $up->every(fn ($item) => $item->execute());
            } catch (Throwable $th) {
                $success = false;
                error($th->getMessage())->out(false);
            }

            if ($success) {
                $print->push(' DONE')->textGreen()->newLines();
                continue;
            }

            $print->push('FAIL')->textRed()->newLines();
        }

        $print->out();

        return $this->seed();
    }

    /**
     * Drops and recreates the database, then runs all migrations from scratch.
     *
     * This method is typically used to reset the database to a clean state
     * and apply all migrations in order. It respects the `$silent` flag
     * to suppress prompts and output, and the `--dry-run` option to
     * preview SQL queries without executing them.
     *
     * @param bool $silent If `true`, suppresses prompts and outputs; otherwise prompts may be shown.
     * @return int Exit code indicating the result of running migrations:
     *             0 on success, 2 if aborted due to environment or user confirmation failure,
     *             1 on general failure.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function fresh(bool $silent = false): int
    {
        // drop and recreate database
        if (($drop = $this->databaseDrop($silent)) > 0) {
            return $drop;
        }
        if (($create = $this->databaseCreate(true)) > 0) {
            return $create;
        }

        // run migration

        $print   = new Style();
        $migrate = $this->baseMigrate()->sort();
        $width   = $this->getWidth(40, 60);

        /** @noinspection DuplicatedCode */
        $print->tap(info('Running migration'));

        foreach ($migrate as $key => $val) {
            $schema = require_once $val['file_name'];
            $up     = new Collection($schema['up'] ?? []);

            if ($this->option('dry-run')) {
                $up->each(function ($item) use ($print) {
                    $print->push($item->__toString())->textDim()->newLines(2);

                    return true;
                });
                continue;
            }

            $print->push($key)->textDim();
            $print->repeat('.', $width - strlen($key))->textDim();

            try {
                $success = $up->every(fn ($item) => $item->execute());
            } catch (Throwable $th) {
                $success = false;
                error($th->getMessage())->out(false);
            }

            if ($success) {
                $print->push(' DONE')->textGreen()->newLines();
                continue;
            }

            $print->push('FAIL')->textRed()->newLines();
        }

        $print->out();

        return $this->seed();
    }

    /**
     * Roll back all executed migrations.
     *
     * @param bool $silent If `true`, suppresses environment checks and user prompts.
     * @return int Exit code indicating the result of the rollback operation:
     *             0 on success, 2 if aborted due to environment restrictions or confirmation failure.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     */
    public function reset(bool $silent = false): int
    {
        if (false === $this->runInDev() && false === $silent) {
            return 2;
        }

        info('Rolling back all migrations')->out(false);

        return $this->rollbacks(false, 0);
    }

    /**
     * Reset all migrations and immediately re-run them.
     *
     * @return int Exit code indicating the result of the refresh operation:
     *             0 on success, 2 if aborted due to environment restrictions,
     *             or a propagated non-zero code from reset or migration.
     * @return int
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     */
    public function refresh(): int
    {
        if (false === $this->runInDev()) {
            return 2;
        }

        if (($reset = $this->reset(true)) > 0) {
            return $reset;
        }
        if (($migration = $this->migration(true)) > 0) {
            return $migration;
        }

        return 0;
    }

    /**
     * Roll back one or more batches of migrations.
     *
     * @return int Exit code indicating the result of the rollback operation:
     *             0 on success, 1 if required options are missing or invalid.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function rollback(): int
    {
        if (false === ($batch = $this->option('batch', false))) {
            error('batch is required.')->out();

            return 1;
        }
        $take    = $this->take;
        $message = "Rolling {$take} back migrations.";
        if ($take < 0) {
            $take    = 0;
            $message = 'Rolling back migrations.';
        }
        info($message)->out(false);

        return $this->rollbacks((int) $batch, (int) $take);
    }

    /**
     * Roll back executed migrations based on batch number and limit.
     *
     * @param false|int $batch The batch number to roll back, or `false` to determine it automatically.
     * @param int $take The number of batches to roll back starting from the given batch.
     * @return int Exit code indicating the result of the rollback process:
     *             always returns 0 after processing the selected migrations.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function rollbacks(false|int $batch, int $take): int
    {
        $print   = new Style();
        $width   = $this->getWidth(40, 60);

        $migrate = false === $batch
            ? $this->baseMigrate($batch)
            : $this->baseMigrate($batch)->filter(static fn ($value): bool => $value['batch'] >= $batch - $take);

        foreach ($migrate->sortDesc() as $key => $val) {
            $schema = require_once $val['file_name'];
            $down   = new Collection($schema['down'] ?? []);

            if ($this->option('dry-run')) {
                $down->each(function ($item) use ($print) {
                    $print->push($item->__toString())->textDim()->newLines(2);

                    return true;
                });
                continue;
            }

            $print->push($key)->textDim();
            $print->repeat('.', $width - strlen($key))->textDim();

            try {
                $success = $down->every(fn ($item) => $item->execute());

                if ($success) {
                    $success = $this->deleteMigrationTable((int) $val['batch']);
                }
            } catch (Throwable $th) {
                $success = false;
                error($th->getMessage())->out(false);
            }

            if ($success) {
                $print->push(' DONE')->textGreen()->newLines();
                continue;
            }

            $print->push('FAIL')->textRed()->newLines();
        }

        $print->out();

        return 0;
    }

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
    public function databaseCreate(bool $silent = false): int
    {
        $dbName  = $this->DbName();
        $message = style("Do you want to create database `{$dbName}`?")->textBlue();

        if (false === $silent && (!$this->runInDev() || !$this->confirmation($message))) {
            return 2;
        }

        info("creating database `{$dbName}`")->out(false);

        $success = Schema::create()->database($dbName)->ifNotExists()->execute();

        if ($success) {
            success("success create database `{$dbName}`")->out(false);

            $this->initializeMigration();

            return 0;
        }

        error("cant created database `{$dbName}`")->out(false);

        return 1;
    }

    /**
     * Drop the target database after confirmation and environment validation.
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
    public function databaseDrop(bool $silent = false): int
    {
        $dbName  = $this->DbName();
        $message = style("Do you want to drop database `{$dbName}`?")->textRed();

        if (false === $silent && (!$this->runInDev() || !$this->confirmation($message))) {
            return 2;
        }

        info("try to drop database `{$dbName}`")->out(false);

        $success = Schema::drop()->database($dbName)->ifExists()->execute();

        if ($success) {
            success("success drop database `{$dbName}`")->out(false);

            return 0;
        }

        error("cant drop database `{$dbName}`")->out(false);

        return 1;
    }

    /**
     * Display information about the current database or a specific table.
     *
     * @return int Exit code indicating the result:
     *             0 on success, 2 if no tables are found or the database is empty.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function databaseShow(): int
    {
        if ($this->option('table-name')) {
            return $this->tableShow($this->option('table-name'));
        }

        $dbName = $this->DbName();
        $width  = $this->getWidth(40, 60);
        info('showing database')->out(false);

        $tables = PDO::query('SHOW DATABASES')
            ->query('
                SELECT table_name, create_time, ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024) AS `size`
                FROM information_schema.tables
                WHERE table_schema = :db_name')
            ->bind(':db_name', $dbName)
            ->resultset();

        if (0 === count($tables)) {
            warn('table is empty try to run migration')->out();

            return 2;
        }

        foreach ($tables as $table) {
            $table  = array_change_key_case($table);
            $name   = $table['table_name'];
            $time   = $table['create_time'];
            $size   = $table['size'];
            $length = strlen($name) + strlen($time) + strlen($size);

            style($name)
                ->push(' ' . $size . ' Mb ')->textDim()
                ->repeat('.', $width - $length)->textDim()
                ->push(' ' . $time)
                ->out();
        }

        return 0;
    }

    /**
     * Display detailed column information for a specific database table.
     *
     * @param string $table The name of the table to inspect.
     * @return int Exit code indicating the result:
     *             always returns 0 after printing the table structure.
     */
    public function tableShow(string $table): int
    {
        $table = DB::table($table)->info();
        $print = new Style("\n");
        $width = $this->getWidth(40, 60);

        $print->push('column')->textYellow()->bold()->resetDecorate()->newLines();
        foreach ($table as $column) {
            $willPrint = [];

            if ($column['IS_NULLABLE'] === 'YES') {
                $willPrint[] = 'nullable';
            }
            if ($column['COLUMN_KEY'] === 'PRI') {
                $willPrint[] = 'primary';
            }

            $info   = implode(', ', $willPrint);
            $length = strlen($column['COLUMN_NAME']) + strlen($column['COLUMN_TYPE']) + strlen($info);

            $print->push($column['COLUMN_NAME'])->bold()->resetDecorate();
            $print->push(' ' . $info . ' ')->textDim();
            $print->repeat('.', $width - $length)->textDim();
            $print->push(' ' . $column['COLUMN_TYPE']);
            $print->newLines();
        }

        $print->out();

        return 0;
    }

    /**
     * Display the current migration status and batch numbers.
     *
     * @return int Exit code indicating the result:
     *             always returns 0 after printing migration statuses.
     */
    public function status(): int
    {
        $print = new Style();
        $print->tap(info('show migration status'));
        $width = $this->getWidth(40, 60);
        foreach ($this->getMigrationTable() as $migration_name => $batch) {
            $length = strlen($migration_name) + strlen((string) $batch);
            $print
                ->push($migration_name)
                ->push(' ')
                ->repeat('.', $width - $length)->textDim()
                ->push(' ')
                ->push($batch)
                ->newLines();
        }

        $print->out();

        return 0;
    }

    /**
     * Execute seeders after migrations based on the provided options.
     *
     * @return int Exit code indicating the result:
     *             0 if no seeding is performed or on success,
     *             otherwise the exit code returned by the seeder command.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    private function seed(): int
    {
        if ($this->option('dry-run', false)) {
            return 0;
        }
        if ($this->seed) {
            $seed = true === $this->seed ? null : $this->seed;

            return new SeedCommand([], ['class' => $seed])->main();
        }

        $namespace = $this->option('seed-namespace', false);
        if ($namespace) {
            $namespace = true === $namespace ? null : $namespace;

            return new SeedCommand([], ['name-space' => $namespace])->main();
        }

        return 0;
    }

    /**
     * Determine whether the migration table exists in the current database.
     *
     * @return bool Returns true if the migration table exists, false otherwise.
     */
    private function hasMigrationTable(): bool
    {
        $result = PDO::query(
            "SELECT COUNT(table_name) as total
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = 'migration'"
        )->single();

        if ($result) {
            return $result['total'] > 0;
        }

        return false;
    }

    /**
     * Create the migration table schema in the current database.
     *
     * @return bool Returns true on successful creation, false on failure.
     */
    private function createMigrationTable(): bool
    {
        return Schema::table('migration', function (Create $column) {
            $column('migration')->varchar(100)->notNull();
            $column('batch')->int(4)->notNull();

            $column->unique('migration');
        })->execute();
    }

    /**
     * Retrieve the list of executed migrations and their batch numbers.
     *
     * @return Collection<string, int> A collection mapping migration names to their batch numbers.
     */
    private function getMigrationTable(): Collection
    {
        /** @var Collection<string, int> $pair */
        $pair = DB::table('migration')
            ->select()
            ->get()
            ->assocBy(static fn ($item) => [$item['migration'] => (int) $item['batch']]);

        return $pair;
    }

    /**
     * Insert a migration record into the migration table.
     *
     * @param array<string, string|int> $migration The migration name and its associated batch number.
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

    /**
     * Initialize the migration system by creating the migration table if it does not exist.
     *
     * @return int Exit code indicating the result:
     *             0 if the migration table already exists or is successfully created,
     *             1 if the migration table creation fails.
     */
    public function initializeMigration(): int
    {
        if ($this->hasMigrationTable()) {
            info('Migration table already exist on your database table.')->out(false);

            return 0;
        }

        if ($this->createMigrationTable()) {
            success('Success create migration table.')->out(false);

            return 0;
        }

        error('Migration table cant be create.')->out(false);

        return 1;
    }

    /**
     * Register an additional vendor directory to be scanned for migration files.
     *
     * @param string $path Absolute or relative path to the vendor migration directory.
     * @return void
     */
    public static function addVendorMigrationPath(string $path): void
    {
        static::$vendorPaths[] = $path;
    }

    /**
     * Remove all previously registered vendor migration paths.
     *
     * @reurn void
     */
    public static function flushVendorMigrationPaths(): void
    {
        static::$vendorPaths = [];
    }
}
