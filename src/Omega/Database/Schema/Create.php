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

/**
 * Class Create
 *
 * Provides methods to create databases and tables.
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
readonly class Create
{
    /**
     * @param SchemaConnectionInterface $pdo          Database connection
     * @param string|null               $databaseName Optional database name
     */
    public function __construct(
        private SchemaConnectionInterface $pdo,
        private ?string $databaseName = null,
    ) {
    }

    /**
     * Create a new database.
     *
     * @param string $databaseName Name of the database to create
     * @return DB\Create Instance of DB\Create to execute database creation
     */
    public function database(string $databaseName): DB\Create
    {
        return new DB\Create($databaseName, $this->pdo);
    }

    /**
     * Create a new table.
     *
     * @param string $tableName Name of the table to create
     * @return Table\Create Instance of Table\Create to define table structure
     */
    public function table(string $tableName): Table\Create
    {
        return new Table\Create($this->databaseName, $tableName, $this->pdo);
    }
}
