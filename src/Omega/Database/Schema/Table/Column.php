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

use Omega\Database\Schema\Table\Attributes\Alter\DataType as AlterDataType;
use Omega\Database\Schema\Table\Attributes\DataType;

/**
 * Class Column
 *
 * Represents a table column definition for schema operations.
 * Can handle new column creation, column alterations, or raw SQL.
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
class Column
{
    /**
     * The column query representation.
     *
     * Can be a column name with a DataType (for new columns),
     * an AlterDataType (for altering columns),
     * or a raw SQL string.
     *
     * @var string|DataType|AlterDataType
     */
    protected string|DataType|AlterDataType $query;

    /**
     * Convert the column definition to a string.
     *
     * @return string The SQL representation of the column
     */
    public function __toString(): string
    {
        return (string) $this->query;
    }

    /**
     * Define a new column with a DataType.
     *
     * @param string $columnName Column name
     * @return DataType Returns a DataType instance to define constraints
     */
    public function column(string $columnName): DataType
    {
        return $this->query = new DataType($columnName);
    }

    /**
     * Define a column for alteration with AlterDataType.
     *
     * @param string $columnName Column name
     * @return AlterDataType Returns an AlterDataType instance to define modifications
     */
    public function alterColumn(string $columnName): AlterDataType
    {
        return $this->query = new AlterDataType($columnName);
    }

    /**
     * Set a raw SQL query for the column.
     *
     * @param string $query Raw SQL string
     * @return $this Fluent interface
     */
    public function raw(string $query): self
    {
        $this->query = $query;

        return $this;
    }
}
