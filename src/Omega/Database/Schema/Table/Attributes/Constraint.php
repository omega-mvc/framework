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

namespace Omega\Database\Schema\Table\Attributes;

use Exception;

/**
 * Class Constraint
 *
 * Represents column constraints and options for table creation.
 * Handles data type, nullability, default values, auto increment, unsigned, raw SQL, and column order.
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
class Constraint
{
    /** @var string Column data type (e.g., int, varchar) */
    protected string $dataType;

    /** @var string Nullable constraint (NULL or NOT NULL) */
    protected string $nullable;

    /** @var string Default value constraint */
    protected string $default;

    /** @var string AUTO_INCREMENT constraint */
    protected string $autoIncrement;

    /** @var string Column order (FIRST, AFTER column) */
    protected string $order;

    /** @var string UNSIGNED constraint for numeric types */
    protected string $unsigned;

    /** @var string Raw SQL appended to column definition */
    protected string $raw;

    /**
     * @param string $data_type Column data type
     */
    public function __construct(string $data_type)
    {
        $this->dataType      = $data_type;
        $this->nullable      = '';
        $this->default       = '';
        $this->autoIncrement = '';
        $this->raw           = '';
        $this->order         = '';
        $this->unsigned      = '';
    }

    /**
     * Convert constraint to SQL string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->query();
    }

    /**
     * Build the SQL string for the column definition.
     *
     * @return string
     */
    private function query(): string
    {
        $column = [
            $this->dataType,
            $this->unsigned,
            $this->nullable,
            $this->default,
            $this->autoIncrement,
            $this->raw,
            $this->order,
        ];

        return implode(' ', array_filter($column, fn ($item) => $item !== ''));
    }

    /**
     * Set column as NOT NULL or NULL.
     *
     * @param bool $notNull
     * @return $this
     */
    public function notNull(bool $notNull = true): self
    {
        $this->nullable = $notNull ? 'NOT NULL' : 'NULL';

        return $this;
    }

    /**
     * Alias for notNull() method.
     *
     * @param bool $null
     * @return $this
     */
    public function null(bool $null = true): self
    {
        return $this->notNull(!$null);
    }

    /**
     * Set default value for the column.
     *
     * @param string|int $default Default value
     * @param bool       $wrap    Wrap value in quotes (ignored for integers)
     * @return $this
     */
    public function default(string|int $default, bool $wrap = true): self
    {
        $wrap          = is_int($default) ? false : $wrap;
        $this->default = $wrap ? "DEFAULT '{$default}'" : "DEFAULT {$default}";

        return $this;
    }

    /**
     * Set default value as NULL.
     *
     * @return $this
     */
    public function defaultNull(): self
    {
        return $this->default('NULL', false);
    }

    /**
     * Enable or disable AUTO_INCREMENT.
     *
     * @param bool $increment
     * @return $this
     */
    public function autoIncrement(bool $increment = true): self
    {
        $this->autoIncrement = $increment ? 'AUTO_INCREMENT' : '';

        return $this;
    }

    /**
     * Alias for autoIncrement() method.
     *
     * @param bool $increment
     * @return $this
     */
    public function increment(bool $increment): self
    {
        return $this->autoIncrement($increment);
    }

    /**
     * Mark numeric column as UNSIGNED.
     *
     * @return $this
     * @throws Exception If column data type is not integer type
     */
    public function unsigned(): self
    {
        if (false === preg_match('/^(int|tinyint|bigint|smallint)(\(\d+\))?$/', $this->dataType)) {
            throw new Exception('Cant use UNSIGNED not integer datatype.');
        }
        $this->unsigned = 'UNSIGNED';

        return $this;
    }

    /**
     * Append raw SQL to column definition.
     *
     * @param string $raw
     * @return $this
     */
    public function raw(string $raw): self
    {
        $this->raw = $raw;

        return $this;
    }
}
