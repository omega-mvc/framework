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

declare(strict_types=1);

namespace Omega\Database\Schema\Table;

use Omega\Database\Schema\Query;
use Omega\Database\Schema\SchemaConnectionInterface;
use Omega\Database\Schema\Table\Attributes\DataType;

use function array_merge;
use function count;
use function implode;

/**
 * Class Create
 *
 * Handles creation of database tables with columns, primary keys, unique constraints,
 * storage engines, and character sets. Extends the Query class to build executable SQL.
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
class Create extends Query
{
    /** @var string Storage engine constants. */
    public const string INNODB    = 'INNODB';
    public const string MYISAM    = 'MYISAM';
    public const string MEMORY    = 'MEMORY';
    public const string MERGE     = 'MERGE';
    public const string EXAMPLE   = 'EXAMPLE';
    public const string ARCHIVE   = 'ARCHIVE';
    public const string CSV       = 'CSV';
    public const string BLACKHOLE = 'BLACKHOLE';
    public const string FEDERATED = 'FEDERATED';

    /** @var Column[]|DataType[] List of table columns */
    private array $columns;

    /** @var string[] List of primary key columns */
    private array $primaryKeys;

    /** @var string[] List of unique columns */
    private array $uniques;

    /** @var string Storage engine for the table */
    private string $storeEngine;

    /** @var string Character set for the table */
    private string $characterSet;

    /** @var string Fully qualified table name (database.table) */
    private string $tableName;

    /**
     * Create constructor.
     *
     * @param string $databaseName Database name
     * @param string $tableName    Table name
     * @param SchemaConnectionInterface $pdo Database connection interface
     */
    public function __construct(string $databaseName, string $tableName, SchemaConnectionInterface $pdo)
    {
        $this->tableName    = $databaseName . '.' . $tableName;
        $this->pdo          = $pdo;
        $this->columns      = [];
        $this->primaryKeys  = [];
        $this->uniques      = [];
        $this->storeEngine  = '';
        $this->characterSet = '';
    }

    /**
     * Add a new column by name using fluent interface.
     *
     * @param string $columnName Name of the column
     * @return DataType Returns a DataType instance to define column constraints
     */
    public function __invoke(string $columnName): DataType
    {
        return $this->columns[] = new Column()->column($columnName);
    }

    /**
     * Add an empty column instance.
     *
     * @return Column Column instance
     */
    public function addColumn(): Column
    {
        return $this->columns[] = new Column();
    }

    /**
     * Set multiple columns at once.
     *
     * @param Column[] $columns Array of Column instances
     * @return $this Fluent interface
     */
    public function columns(array $columns): self
    {
        $this->columns = [];
        foreach ($columns as $column) {
            $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * Define a primary key column.
     *
     * @param string $columnName Column name
     * @return $this Fluent interface
     */
    public function primaryKey(string $columnName): self
    {
        $this->primaryKeys[] = $columnName;

        return $this;
    }

    /**
     * Define a unique constraint column.
     *
     * @param string $unique Column name
     * @return $this Fluent interface
     */
    public function unique(string $unique): self
    {
        $this->uniques[] = $unique;

        return $this;
    }

    /**
     * Set the storage engine for the table.
     *
     * @param string $engine Storage engine (use constants)
     * @return $this Fluent interface
     */
    public function engine(string $engine): self
    {
        $this->storeEngine = $engine;

        return $this;
    }

    /**
     * Set the character set for the table.
     *
     * @param string $characterSet Character set name
     * @return $this Fluent interface
     */
    public function character(string $characterSet): self
    {
        $this->characterSet = $characterSet;

        return $this;
    }

    /**
     * Build the CREATE TABLE SQL statement.
     *
     * @return string SQL query string
     */
    protected function builder(): string
    {
        $columns = array_merge($this->getColumns(), $this->getPrimaryKey(), $this->getUnique());
        $columns = $this->join($columns, ', ');
        $query   = $this->join([
            $this->tableName, '(', $columns, ')' . $this->getStoreEngine() . $this->getCharacterSet()
        ]);

        return 'CREATE TABLE ' . $query;
    }

    /**
     * Get SQL string for all columns.
     *
     * @return string[] Array of column SQL strings
     */
    private function getColumns(): array
    {
        $res = [];

        foreach ($this->columns as $attribute) {
            $res[] = $attribute->__toString();
        }

        return $res;
    }

    /**
     * Get SQL string for primary key constraint.
     *
     * @return string[] Array with PRIMARY KEY SQL
     */
    private function getPrimaryKey(): array
    {
        if (count($this->primaryKeys) === 0) {
            return [''];
        }

        $primaryKeys = implode(', ', $this->primaryKeys);

        return ["PRIMARY KEY ($primaryKeys)"];
    }

    /**
     * Get SQL string for unique constraints.
     *
     * @return string[] Array with UNIQUE SQL
     */
    private function getUnique(): array
    {
        if (count($this->uniques) === 0) {
            return [''];
        }

        $uniques = implode(', ', $this->uniques);

        return ["UNIQUE ($uniques)"];
    }

    /**
     * Get SQL string for storage engine.
     *
     * @return string Storage engine SQL
     */
    private function getStoreEngine(): string
    {
        return $this->storeEngine === '' ? '' : ' ENGINE=' . $this->storeEngine;
    }

    /**
     * Get SQL string for character set.
     *
     * @return string Character set SQL
     */
    private function getCharacterSet(): string
    {
        return $this->characterSet === '' ? '' : " CHARACTER SET " . $this->characterSet;
    }
}
