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
use Omega\Database\Schema\Traits\ConditionTrait;

/**
 * Class Drop
 *
 * Handles the generation of a DROP TABLE SQL statement.
 * Uses ConditionTrait for optional conditions (e.g., IF EXISTS).
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
class Drop extends Query
{
    use ConditionTrait;

    /** @var string Fully qualified table name (database.table) */
    private string $tableName;

    /**
     * Drop constructor.
     *
     * @param string                    $databaseName Name of the database
     * @param string                    $tableName    Name of the table
     * @param SchemaConnectionInterface $pdo          Database connection interface
     */
    public function __construct(string $databaseName, string $tableName, SchemaConnectionInterface $pdo)
    {
        $this->tableName = $databaseName . '.' . $tableName;
        $this->pdo       = $pdo;
    }

    /**
     * Build the DROP TABLE SQL statement.
     *
     * @return string SQL query string
     */
    protected function builder(): string
    {
        $condition = $this->join([$this->ifExists, $this->tableName]);

        return 'DROP TABLE ' . $condition . ';';
    }
}
