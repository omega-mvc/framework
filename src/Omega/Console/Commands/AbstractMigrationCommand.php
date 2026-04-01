<?php /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Console\Commands;

use DirectoryIterator;
use Exception;
use Omega\Collection\Collection;
use Omega\Console\AbstractCommand;
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
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

/**
 * BaseMigrationCommand
 * Fornisce i mattoncini per costruire i comandi di database e migrazione.yes
 */
abstract class AbstractMigrationCommand extends AbstractCommand
{
    public static array $vendorPaths = [];

    /**
     * Opzioni comuni indispensabili.
     * Rimosso --yes: troppo rischioso e ridondante con -n di Symfony.
     */
    protected function addDatabaseOption(): self
    {
        $this->addOption('database', 'd', InputOption::VALUE_OPTIONAL, 'The database connection to use');

        return $this;
    }

    protected function addTableNameOption(): self
    {
        $this->addOption(
            'table-name',     // nome dell'opzione
            't',              // alias breve
            InputOption::VALUE_OPTIONAL, // opzionale, accetta un valore
            'Show a specific table structure' // descrizione
        );

        return $this;
    }

    protected function addForceOption(): self
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when in production');

        return $this;
    }

    /**
     * Opzione per l'esecuzione simulata.
     */
    protected function addDryRunOption(): self
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dump the SQL queries without executing');

        return $this;
    }

    /**
     * Opzione per saltare le domande di conferma.
     */
    protected function addYesOption(): self
    {
        $this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Do not ask for confirmation (Assume "yes")');

        return $this;
    }

    /**
     * Opzioni per il seeding post-migrazione.
     */
    protected function addSeedOption(): self
    {
        $this->addOption('seed', null, InputOption::VALUE_NONE, 'Seed the database after migrating');

        return $this;
    }

    protected function addSeedNamespaceOption(): self
    {
        $this->addOption('seed-namespace', null, InputOption::VALUE_OPTIONAL, 'Seed using a specific namespace');

        return $this;
    }

    /**
     * Opzioni per la gestione dei batch e dei limiti (Rollback).
     */
    protected function addBatchOption(): self
    {
        $this->addOption('batch', null, InputOption::VALUE_OPTIONAL, 'The batch number to rollback');

        return $this;
    }

    protected function addTakeOption(): self
    {
        $this->addOption('take', null, InputOption::VALUE_OPTIONAL, 'Limit the number of migrations to run');

        return $this;
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
    protected function DbName(): string
    {
        // Symfony ritorna null se l'opzione --database non è presente
        $database = $this->getOption('database');

        return $database ?? $this->app->get(SchemaConnection::class)->getDatabase();
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
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     */
    protected function runInDev(): bool
    {
        if ($this->app->isDev() || $this->getOption('force')) {
            return true;
        }

        return $this->io->confirm(
            '<fg=red;options=bold>Running migration/database in production?</> Continue?',
            false
        );
    }

    /**
     * Prompt the user for confirmation with a yes/no question.
     *
     * This method displays a prompt message to the user and waits for a response.
     * If the `--yes` option is provided, the method automatically returns `true`
     * without asking.
     *
     * @param string $message The message to display in the prompt. Can be a string or a styled message.
     * @return bool Returns `true` if the user confirms, otherwise `false`.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     */
    protected function confirmation(string $message): bool
    {
        if ($this->getOption('yes')) {
            return true;
        }

        $choice = $this->io->choice(
            "<fg=red;options=bold>" . $message . "</>",
            ['no', 'yes'],
            'no'
        );

        return $choice === 'yes';
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
    public function baseMigrate(false|int &$batch = false, bool $register = true): Collection
    {
        $migrationBatch = $this->getMigrationTable();

        $higher = $migrationBatch->length() > 0
            ? $migrationBatch->max() + 1
            : 0;

        $batch = false === $batch ? $higher : $batch;

        $paths   = [$this->app->get('path.migration'), ...static::$vendorPaths];
        $migrate = new Collection([]);

        foreach ($paths as $dir) {
            foreach (new DirectoryIterator($dir) as $file) {
                if ($file->isDot() || $file->isDir()) {
                    continue;
                }

                $migrationName = pathinfo($file->getBasename(), PATHINFO_FILENAME);
                $hasMigration  = $migrationBatch->has($migrationName);

                $filePath = rtrim($dir, '/') . '/' . $file->getFilename();

                // Caso: migrate (up) → nuova migration
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

                // Caso: rollback / refresh / status
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
     * @throws ExceptionInterface
     */
    public function migration(bool $silent = false): int
    {
        // 1. Controllo Ambiente
        if (false === $this->runInDev() && false === $silent) {
            return self::INVALID;
        }

        // 2. Calcolo Larghezza Terminale (Standard Symfony 8)
        // Se non riesce a rilevarla, il default è 80
        $width = min($this->terminal->getWidth() - 20, 60);

        $batch = false;
        $migrate = $this->baseMigrate($batch);

        $migrate = $migrate
            ->filter(static fn ($value): bool => (int) $value['batch'] === (int) $batch)
            ->sort();

        if ($migrate->isEmpty()) {
            $this->io->info('Nothing to migrate.');
            return self::SUCCESS;
        }

        $this->io->title('Running migrations');

        foreach ($migrate as $key => $val) {
            $schema = require_once $val['file_name'];
            $up = new Collection($schema['up'] ?? []);

            if ($this->getOption('dry-run')) {
                $up->each(function ($item) {
                    $this->io->writeln("<fg=gray>{$item->__toString()}</>");
                    $this->io->newLine();
                    return true;
                });
                continue;
            }

            // 3. Output Allineato
            // Usiamo write() per restare sulla stessa riga
            $this->io->write("<fg=gray>" . $key . "</>");

            $dotCount = max(0, $width - strlen($key));
            if ($dotCount > 0) {
                $this->io->write("<fg=gray>" . str_repeat('.', $dotCount) . "</>");
            }

            try {
                $success = $up->every(fn ($item) => $item->execute());

                if ($success) {
                    $this->io->writeln(' <info>DONE</info>');
                } else {
                    $this->io->writeln(' <error>FAIL</error>');
                }
            } catch (Throwable $th) {
                $this->io->newLine();
                $this->io->error($th->getMessage());
                return self::FAILURE;
            }
        }

        $this->io->newLine();

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
        $batch   = false;
        $migrate = $this->baseMigrate($batch)->sort();
        $width = min($this->terminal->getWidth() - 20, 60); //

        $this->io->title('Running migration');

        foreach ($migrate as $key => $val) {
            $schema = require_once $val['file_name'];
            $up     = new Collection($schema['up'] ?? []);

            if ($this->getOption('dry-run')) {
                $up->each(function ($item) {
                    $this->io->writeln("<fg=gray>{$item->__toString()}</>");
                    $this->io->newLine();
                    return true;
                });
                continue;
            }

            // output allineato
            $this->io->write("<fg=gray>{$key}</>");
            $dotCount = max(0, $width - strlen($key));
            if ($dotCount > 0) {
                $this->io->write("<fg=gray>" . str_repeat('.', $dotCount) . "</>");
            }

            try {
                $success = $up->every(fn ($item) => $item->execute());
            } catch (Throwable $th) {
                $this->io->newLine();
                $this->io->error($th->getMessage());
                $success = false;
            }

            if ($success) {
                $this->io->writeln(' <info>DONE</info>');
            } else {
                $this->io->writeln(' <error>FAIL</error>');
            }
        }

        $this->io->newLine();

        return $this->seed();
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
        $message = "Do you want to create database `{$dbName}`?";

        // Controllo ambiente e conferma
        if (false === $silent && (!$this->runInDev() || !$this->confirmation($message))) {
            return 2;
        }

        // Output informativo
        $this->io->writeln("<info>Creating database `{$dbName}`...</info>");

        $success = Schema::create()->database($dbName)->ifNotExists()->execute();

        if ($success) {
            $this->io->success("Successfully created database `{$dbName}`");

            $this->initializeMigration();

            return self::SUCCESS;
        }

        $this->io->error("Cannot create database `{$dbName}`");

        return self::FAILURE;
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
        $message = "Do you want to drop database `{$dbName}`?";

        // Controllo ambiente e conferma
        if (false === $silent && (!$this->runInDev() || !$this->confirmation($message))) {
            return 2;
        }

        // Output informativo
        $this->io->writeln("<comment>Trying to drop database `{$dbName}`...</comment>");

        $success = Schema::drop()->database($dbName)->ifExists()->execute();

        if ($success) {
            $this->io->success("Successfully dropped database `{$dbName}`");

            return self::SUCCESS;
        }

        $this->io->error("Cannot drop database `{$dbName}`");

        return self::FAILURE;
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
        // Se è stato passato il nome della tabella, usiamo il metodo dedicato
        if ($this->getOption('table-name')) {
            return $this->tableShow($this->getOption('table-name'));
        }

        $dbName = $this->DbName();

        // SymfonyStyle
        $io = $this->io;

        // Calcolo larghezza per allineamento output
        $width = min($this->terminal->getWidth() - 20, 60);

        $io->info("Showing database `{$dbName}`...");

        $tables = PDO::query('SHOW DATABASES')
            ->query('
            SELECT table_name, create_time, ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024) AS `size`
            FROM information_schema.tables
            WHERE table_schema = :db_name')
            ->bind(':db_name', $dbName)
            ->resultset();

        if (empty($tables)) {
            $io->warning('Database is empty, try to run migration.');
            return self::INVALID;
        }

        foreach ($tables as $table) {
            $table  = array_change_key_case($table);
            $name   = $table['table_name'];
            $time   = $table['create_time'];
            $size   = $table['size'];
            $length = strlen($name) + strlen((string)$time) + strlen((string)$size);

            // Output stile Symfony: nome + size + punti + time
            $dots = str_repeat('.', max(0, $width - $length));
            $io->writeln(sprintf(
                "<fg=cyan>%s</> <fg=gray>%s Mb</>%s <fg=yellow>%s</>",
                $name,
                $size,
                $dots,
                $time
            ));
        }

        return self::SUCCESS;
    }

    /**
     * Display detailed column information for a specific database table.
     *
     * @param string $tableName The name of the table to inspect.
     * @return int Exit code indicating the result:
     *             always returns 0 after printing the table structure.
     */
    public function tableShow(string $tableName): int
    {
        // Recuperiamo informazioni sulle colonne
        $columns = DB::table($tableName)->info();

        // SymfonyStyle per output colorato
        $io = $this->io;

        // Calcolo larghezza terminal per allineamento
        $width = min($this->terminal->getWidth() - 20, 60);

        $io->section("Columns of table `$tableName`");

        // Header
        $io->writeln(sprintf("<fg=yellow>%s</>", 'COLUMN'));

        foreach ($columns as $column) {
            $willPrint = [];

            if ($column['IS_NULLABLE'] === 'YES') {
                $willPrint[] = 'nullable';
            }
            if ($column['COLUMN_KEY'] === 'PRI') {
                $willPrint[] = 'primary';
            }

            $info   = implode(', ', $willPrint);
            $length = strlen($column['COLUMN_NAME']) + strlen($column['COLUMN_TYPE']) + strlen($info);

            // Creiamo la riga formattata: nome colonna + info + punti + tipo
            $dots = str_repeat('.', max(0, $width - $length));

            $io->writeln(sprintf(
                "<options=bold>%s</> <fg=gray>%s</>%s %s",
                $column['COLUMN_NAME'],
                $info,
                $dots,
                $column['COLUMN_TYPE']
            ));
        }

        return self::SUCCESS;
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
            $this->io->writeln('<comment>Migration table already exists in your database.</comment>');
            return self::SUCCESS;
        }

        if ($this->createMigrationTable()) {
            $this->io->success('Successfully created migration table.');
            return self::SUCCESS;
        }

        $this->io->error('Migration table cannot be created.');
        return self::FAILURE;
    }

    /**
     * Display the current migration status and batch numbers.
     *
     * @return int Exit code indicating the result:
     *             always returns 0 after printing migration statuses.
     */
    public function status(): int
    {
        $this->io->note('show migration status');

        $width = min($this->terminal->getWidth() - 20, 60);

        foreach ($this->getMigrationTable() as $migrationName => $batch) {
            $length = strlen($migrationName) + strlen((string) $batch);

            $line = $migrationName
                . ' '
                . str_repeat('.', max($width - $length, 0))
                . ' '
                . $batch;

            $this->io->text("<fg=default;options=bold>$line</>");
        }

        return self::SUCCESS; // Symfony-style return
    }

    /**
     * Execute seeders after migrations based on the provided options.
     *
     * @return int Exit code indicating the result:
     *             0 if no seeding is performed or on success,
     *             otherwise the exit code returned by the seeder command.
     */
    protected function seed(): int
    {
        if ($this->getOption('dry-run')) {
            return self::SUCCESS;
        }

        // Recuperiamo il valore dell'opzione --seed
        // In Symfony, se l'opzione è InputOption::VALUE_NONE, torna bool
        $shouldSeed = $this->getOption('seed');

        if (!$shouldSeed) {
            return self::SUCCESS;
        }

        $parameters = [];

        // Gestione namespace se presente
        $namespace = $this->getOption('seed-namespace');
        if ($namespace) {
            $parameters['--name-space'] = $namespace;
        }

        // Usiamo il metodo call() che abbiamo aggiunto alla base per invocare il seeder
        // Assumendo che il comando si chiami 'seed' o 'db:seed'
        try {
            return $this->call('seed', $parameters);
        } catch (Throwable $e) {
            $this->io->error("Seeding failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Determine whether the migration table exists in the current database.
     *
     * @return bool Returns true if the migration table exists, false otherwise.
     */
    protected function hasMigrationTable(): bool
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
    protected function createMigrationTable(): bool
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
    protected function getMigrationTable(): Collection
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
    protected function insertMigrationTable(array $migration): bool
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
    protected function deleteMigrationTable(int $batchNumber): bool
    {
        return DB::table('migration')
            ->delete()
            ->equal('batch', $batchNumber)
            ->execute()
            ;
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

        $this->io->info('Rolling back all migrations');

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
     * @throws Exception|ExceptionInterface Thrown if reading input from STDIN fails during the prompt.
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
        $batch = $this->getOption('batch');

        if ($batch === null) {
            $this->io->error('batch is required.');
            return self::FAILURE;
        }

        $take = (int) $this->getOption('take');
        $message = "Rolling {$take} back migrations.";
        if ($take < 0) {
            $take    = 0;
            $message = 'Rolling back migrations.';
        }

        $this->io->info($message);

        return $this->rollbacks((int) $batch, $take);
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
        $width = min($this->terminal->getWidth() - 20, 60);

        // ❗ IMPORTANTE: register = false
        $migrate = false === $batch
            ? $this->baseMigrate($batch, false)
            : $this->baseMigrate($batch, false)
                ->filter(static fn ($value): bool => $value['batch'] >= $batch - $take);

        foreach ($migrate->sortDesc() as $key => $val) {
            $schema = require_once $val['file_name'];
            $down   = new Collection($schema['down'] ?? []);

            if ($this->getOption('dry-run')) {
                $down->each(function ($item) {
                    $this->io->writeln("<fg=gray>{$item->__toString()}</>");
                    $this->io->newLine(2);
                    return true;
                });
                continue;
            }

            $this->io->write("<fg=gray>{$key}</>");

            $dotCount = max(0, $width - strlen($key));
            if ($dotCount > 0) {
                $this->io->write("<fg=gray>" . str_repeat('.', $dotCount) . "</>");
            }

            try {
                $success = $down->every(fn ($item) => $item->execute());

                if ($success) {
                    $success = $this->deleteMigrationTable((int) $val['batch']);
                }
            } catch (Throwable $th) {
                // 👉 qui puoi decidere se essere tollerante
                if (str_contains($th->getMessage(), 'Base table or view not found')) {
                    $success = true;
                } else {
                    $success = false;
                    $this->io->error($th->getMessage());
                }
            }

            if ($success) {
                $this->io->writeln(' <info>DONE</info>');
                continue;
            }

            $this->io->writeln(' <error>FAIL</error>');
        }

        $this->io->newLine();

        return self::SUCCESS;
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
