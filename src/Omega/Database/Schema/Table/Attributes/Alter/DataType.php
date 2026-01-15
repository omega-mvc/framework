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

namespace Omega\Database\Schema\Table\Attributes\Alter;

use function array_map;
use function implode;

/**
 * Class DataType
 *
 * Represents a column's data type for table creation or modification.
 * Provides methods to define numeric, date/time, and text types.
 * Each method returns a Constraint instance, except for positional modifiers like AFTER/FIRST.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema\Table\Attributes\Alter
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class DataType
{
    /** @var string Column name */
    private string $name;

    /** @var string|Constraint Column data type or Constraint instance */
    private string|Constraint $datatype;

    /**
     * Constructor.
     *
     * @param string $columnName Name of the column
     */
    public function __construct(string $columnName)
    {
        $this->name     = $columnName;
        $this->datatype = '';
    }

    /**
     * Convert datatype and constraint to SQL string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->query();
    }

    /**
     * Build SQL string for column definition.
     *
     * @return string
     */
    private function query(): string
    {
        return $this->name . ' ' . $this->datatype;
    }

    /**
     * Define INT type.
     *
     * @param int $length Optional length
     * @return Constraint
     */
    public function int(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'int' : "int($length)";
        return $this->datatype = new Constraint($type);
    }

    public function tinyint(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'tinyint' : "tinyint($length)";
        return $this->datatype = new Constraint($type);
    }

    public function smallint(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'smallint' : "smallint($length)";
        return $this->datatype = new Constraint($type);
    }

    public function bigint(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'bigint' : "bigint($length)";
        return $this->datatype = new Constraint($type);
    }

    public function float(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'float' : "float($length)";
        return $this->datatype = new Constraint($type);
    }

    public function time(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'time' : "time($length)";
        return $this->datatype = new Constraint($type);
    }

    public function timestamp(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'timestamp' : "timestamp($length)";
        return $this->datatype = new Constraint($type);
    }

    public function date(): Constraint
    {
        return $this->datatype = new Constraint('date');
    }

    public function varchar(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'varchar' : "varchar($length)";
        return $this->datatype = new Constraint($type);
    }

    public function text(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'text' : "text($length)";
        return $this->datatype = new Constraint($type);
    }

    public function blob(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'blob' : "blob($length)";
        return $this->datatype = new Constraint($type);
    }

    /**
     * Define ENUM type.
     *
     * @param string[] $enums List of possible enum values
     * @return Constraint
     */
    public function enum(array $enums): Constraint
    {
        $enums = array_map(fn ($item) => "'{$item}'", $enums);
        $enum  = implode(', ', $enums);

        return $this->datatype = new Constraint("ENUM ({$enum})");
    }

    /**
     * Set column position after another column.
     *
     * @param string $column Column name to position after
     * @return void
     */
    public function after(string $column): void
    {
        $this->datatype = "AFTER {$column}";
    }

    /**
     * Set column as first in table.
     *
     * @return void
     */
    public function first(): void
    {
        $this->datatype = 'FIRST';
    }
}
