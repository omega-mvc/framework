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

use function array_pad;
use function explode;

/**
 * Class Drop
 *
 * Provides methods to drop databases and tables.
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
readonly class Drop
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
     * Drop an existing database.
     *
     * @param string $databaseName Name of the database to drop
     * @return DB\Drop Instance of DB\Drop to execute database deletion
     */
    public function database(string $databaseName): DB\Drop
    {
        return new DB\Drop($databaseName, $this->pdo);
    }

    /**
     * Drop an existing table.
     *
     * @param string $tableName Name of the table to drop (can be prefixed with database)
     * @return Table\Drop Instance of Table\Drop to execute table deletion
     */
    public function table(string $tableName): Table\Drop
    {
        [$database, $table] = array_pad(explode('.', $tableName, 2), 2, null);
        $database           = $database ?: $this->databaseName;
        $table              = $table ?: $tableName;

        return new Table\Drop($database, $table, $this->pdo);
    }
}
