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

namespace Omega\Database\Schema\Table\Attributes;

/**
 * Class DataType
 *
 * Represents a column's data type and provides methods to define it.
 * Each method returns a Constraint instance, allowing to chain additional column constraints.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema\Table\Attributes
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
     * @param string $column_name Name of the column
     */
    public function __construct(string $column_name)
    {
        $this->name     = $column_name;
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
     * Define column as INT.
     *
     * @param int $length Optional length
     * @return Constraint
     */
    public function int(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'int' : "int({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function tinyint(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'tinyint' : "tinyint({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function smallint(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'smallint' : "smallint({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function bigint(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'bigint' : "bigint({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function float(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'float' : "float({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function decimal(int $precision = 10, int $scale = 2): Constraint
    {
        return $this->datatype = new Constraint("decimal($precision, $scale)");
    }

    public function double(int $precision = 10, int $scale = 2): Constraint
    {
        return $this->datatype = new Constraint("double($precision, $scale)");
    }

    public function boolean(): Constraint
    {
        return $this->datatype = new Constraint('boolean');
    }

    public function time(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'time' : "time({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function timestamp(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'timestamp' : "timestamp({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function date(): Constraint
    {
        return $this->datatype = new Constraint('date');
    }

    public function datetime(): Constraint
    {
        return $this->datatype = new Constraint('datetime');
    }

    public function year(): Constraint
    {
        return $this->datatype = new Constraint('year');
    }

    public function char(int $length = 255): Constraint
    {
        return $this->datatype = new Constraint("char({$length})");
    }

    public function varchar(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'varchar' : "varchar({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function text(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'text' : "text({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function blob(int $length = 0): Constraint
    {
        $type = $length === 0 ? 'blob' : "blob({$length})";
        return $this->datatype = new Constraint($type);
    }

    public function json(): Constraint
    {
        return $this->datatype = new Constraint('json');
    }

    /**
     * Define column as ENUM type.
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
}
