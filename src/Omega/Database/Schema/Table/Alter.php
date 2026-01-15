<?php

/**
 * Part of Omega - Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Database\Schema\Table;

use Omega\Database\Schema\Query;
use Omega\Database\Schema\Table\Attributes\Alter\DataType;
use Omega\Database\Schema\SchemaConnectionInterface;

use function array_merge;
use function implode;

/**
 * Class Alter
 *
 * Provides methods to modify an existing table structure in the database.
 * Supports adding, altering, dropping, and renaming columns.
 * Builds a complete ALTER TABLE SQL statement when executed.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema\Table
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Alter extends Query
{
    /**
     * @var Column[]|DataType[] Columns to be modified
     */
    private array $alterColumns = [];

    /**
     * @var Column[]|DataType[] Columns to be added
     */
    private array $addColumns = [];

    /**
     * @var string[] Columns to be dropped
     */
    private array $dropColumns = [];

    /**
     * @var array<string, string> Columns to be renamed [oldName => newName]
     */
    private array $renameColumns = [];

    /**
     * @var string Fully qualified table name (database.table)
     */
    private string $tableName;

    /**
     * Constructor.
     *
     * @param string $databaseName Name of the database
     * @param string $tableName Name of the table to alter
     * @param SchemaConnectionInterface $pdo PDO connection instance
     */
    public function __construct(string $databaseName, string $tableName, SchemaConnectionInterface $pdo)
    {
        $this->tableName = $databaseName . '.' . $tableName;
        $this->pdo       = $pdo;
    }

    /**
     * Shortcut to create a new column definition.
     *
     * @param string $columnName Column name
     * @return DataType
     */
    public function __invoke(string $columnName): DataType
    {
        return $this->column($columnName);
    }

    /**
     * Add a new column to the table.
     *
     * @param string $columnName Column name
     * @return DataType
     */
    public function add(string $columnName): DataType
    {
        return $this->addColumns[] = new Column()->alterColumn($columnName);
    }

    /**
     * Drop a column from the table.
     *
     * @param string $columnName Column name to drop
     * @return string
     */
    public function drop(string $columnName): string
    {
        return $this->dropColumns[] = $columnName;
    }

    /**
     * Alter an existing column in the table.
     *
     * @param string $columnName Column name to modify
     * @return DataType
     */
    public function column(string $columnName): DataType
    {
        return $this->alterColumns[] = new Column()->alterColumn($columnName);
    }

    /**
     * Rename a column.
     *
     * @param string $from Old column name
     * @param string $to New column name
     * @return string
     */
    public function rename(string $from, string $to): string
    {
        return $this->renameColumns[$from] = $to;
    }

    /**
     * Build the complete ALTER TABLE SQL query.
     *
     * @return string
     */
    protected function builder(): string
    {
        $query = [];

        // Merge alter, add, drop, and rename statements
        $query = array_merge($query, $this->getModify(), $this->getColumns(), $this->getDrops(), $this->getRename());
        $query = implode(', ', $query);

        return "ALTER TABLE {$this->tableName} {$query};";
    }

    /**
     * Build MODIFY COLUMN statements.
     *
     * @return string[]
     */
    private function getModify(): array
    {
        $res = [];

        foreach ($this->alterColumns as $attribute) {
            $res[] = "MODIFY COLUMN {$attribute->__toString()}";
        }

        return $res;
    }

    /**
     * Build RENAME COLUMN statements.
     *
     * @return string[]
     */
    private function getRename(): array
    {
        $res = [];

        foreach ($this->renameColumns as $old => $new) {
            $res[] = "RENAME COLUMN {$old} TO {$new}";
        }

        return $res;
    }

    /**
     * Build ADD COLUMN statements.
     *
     * @return string[]
     */
    private function getColumns(): array
    {
        $res = [];

        foreach ($this->addColumns as $attribute) {
            $res[] = "ADD {$attribute->__toString()}";
        }

        return $res;
    }

    /**
     * Build DROP COLUMN statements.
     *
     * @return string[]
     */
    private function getDrops(): array
    {
        $res = [];

        foreach ($this->dropColumns as $drop) {
            $res[] = "DROP COLUMN {$drop}";
        }

        return $res;
    }
}
