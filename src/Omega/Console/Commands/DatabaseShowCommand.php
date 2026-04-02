<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Support\Facades\DB;
use Omega\Support\Facades\PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'database:show',
    description: 'Show database tables and sizes',
    options: [
        'database'   => ['d', InputOption::VALUE_OPTIONAL, 'The database connection to use'],
        'force'      => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'table-name' => ['t', InputOption::VALUE_OPTIONAL, 'Show a specific table structure']
    ]
)]
final class DatabaseShowCommand extends AbstractMigrationCommand
{
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
    public function __invoke(): int
    {
        if ($this->getOption('table-name')) {
            return $this->tableShow($this->getOption('table-name'));
        }

        $dbName = $this->getDatabaseName();

        $io = $this->io;

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
    private function tableShow(string $tableName): int
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
}
