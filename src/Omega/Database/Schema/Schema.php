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

namespace Omega\Database\Schema;

use Omega\Database\Schema\Table\Alter;
use Omega\Database\Schema\Table\Create as TableCreate;
use Omega\Database\Schema\Table\Raw;
use Omega\Database\Schema\Table\Truncate;

/**
 * Class Schema
 *
 * Provides a fluent interface to manage database schemas.
 * Allows creating, dropping, truncating, and altering tables,
 * as well as running raw SQL statements within a given database connection.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Schema
{
    /**
     * @param SchemaConnectionInterface $pdo          The database connection instance
     * @param string|null               $databaseName Optional database name; defaults to the connection's database
     */
    public function __construct(
        private readonly SchemaConnectionInterface $pdo,
        private ?string $databaseName = null,
    ) {
        $this->databaseName ??= $this->pdo->getDatabase();
    }

    /**
     * Create a new schema.
     *
     * @return Create Instance of Create for schema creation
     */
    public function create(): Create
    {
        return new Create($this->pdo, $this->databaseName);
    }

    /**
     * Drop an existing schema.
     *
     * @return Drop Instance of Drop for schema deletion
     */
    public function drop(): Drop
    {
        return new Drop($this->pdo, $this->databaseName);
    }

    /**
     * Truncate a table (refresh table contents).
     *
     * @param string $tableName Name of the table to truncate
     * @return Truncate Instance of Truncate for clearing table data
     */
    public function refresh(string $tableName): Truncate
    {
        return new Truncate($this->databaseName, $tableName, $this->pdo);
    }

    /**
     * Define a new table schema.
     *
     * @param string                      $tableName Target table name
     * @param callable(TableCreate): void $blueprint Closure to define columns and indexes
     * @return TableCreate Instance of TableCreate with the defined table structure
     */
    public function table(string $tableName, callable $blueprint): TableCreate
    {
        $columns = new TableCreate($this->databaseName, $tableName, $this->pdo);
        $blueprint($columns);

        return $columns;
    }

    /**
     * Alter an existing table structure.
     *
     * @param string                $tableName Target table name
     * @param callable(Alter): void $blueprint Closure to define alterations
     * @return Alter Instance of Alter to modify the table
     */
    public function alter(string $tableName, callable $blueprint): Alter
    {
        $columns = new Alter($this->databaseName, $tableName, $this->pdo);
        $blueprint($columns);

        return $columns;
    }

    /**
     * Execute a raw SQL statement on the database.
     *
     * @param string $raw Raw SQL string
     * @return Raw Instance of Raw to execute the SQL
     */
    public function raw(string $raw): Raw
    {
        return new Raw($raw, $this->pdo);
    }
}
