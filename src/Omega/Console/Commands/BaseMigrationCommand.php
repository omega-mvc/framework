<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Collection\Collection;
use Omega\Database\Schema\Table\Create;
use Omega\Database\Schema\SchemaConnection;
use Omega\Support\Facades\DB;
use Omega\Support\Facades\PDO;
use Omega\Support\Facades\Schema;
use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputOption;

use function Omega\Support\app;

/**
 * BaseMigrationCommand
 * Fornisce i mattoncini per costruire i comandi di database e migrazione.
 *
 * @property ?int        $take
 * @property ?int        $batch
 * @property bool        $force
 * @property string|bool $seed
 */
abstract class BaseMigrationCommand extends AbstractCommand
{
    /**
     * Opzioni comuni indispensabili.
     * Rimosso --yes: troppo rischioso e ridondante con -n di Symfony.
     */
    protected function addDatabaseOption(): self
    {
        $this->addOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use');

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

    protected function DbName(): string
    {
        return $this->option('database', app()->get(SchemaConnection::class)->getDatabase());
    }

    protected function runInDev(): bool
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

    protected function confirmation(Style|string $message): bool
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

    protected function seed(): int
    {
        if ($this->option('dry-run', false)) {
            return 0;
        }

        if ($this->seed) {
            $seed = true === $this->seed ? null : $this->seed;

            return new SeedCommand('', ['class' => $seed])->handle();
        }

        $namespace = $this->option('seed-namespace', false);
        if ($namespace) {
            $namespace = true === $namespace ? null : $namespace;

            return new SeedCommand('', ['name-space' => $namespace])->handle();
        }

        return 0;
    }

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

    protected function createMigrationTable(): bool
    {
        return Schema::table('migration', function (Create $column) {
            $column('migration')->varchar(100)->notNull();
            $column('batch')->int(4)->notNull();

            $column->unique('migration');
        })->execute();
    }

    protected function getMigrationTable(): Collection
    {
        /** @var Collection<string, int> $pair */
        $pair = DB::table('migration')
            ->select()
            ->get()
            ->assocBy(static fn ($item) => [$item['migration'] => (int) $item['batch']]);

        return $pair;
    }

    protected function insertMigrationTable(array $migration): bool
    {
        return DB::table('migration')
            ->insert()
            ->values($migration)
            ->execute()
            ;
    }

    protected function deleteMigrationTable(int $batchNumber): bool
    {
        return DB::table('migration')
            ->delete()
            ->equal('batch', $batchNumber)
            ->execute()
            ;
    }
}
