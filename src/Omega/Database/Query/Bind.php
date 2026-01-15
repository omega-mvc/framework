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

namespace Omega\Database\Query;

/**
 * Represents a single PDO bind parameter.
 *
 * This class encapsulates the bind name, its value, and the related column name.
 * It also supports bind name prefixing to avoid collisions in complex queries
 * such as multi-row inserts, joins, or subqueries.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Query
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
final class Bind
{
    /** @var string Bind identifier without prefix. */
    private string $bind;

    /** @var mixed Value associated with the bind parameter. */
    private mixed $bindValue;

    /** @var string Related column name, used to build SQL clauses. */
    private string $columnName;

    /** @var string Prefix applied to the bind name (e.g. ":", ":bind_1_"). */
    private string $prefixBind;

    /**
     * Create a new bind instance.
     *
     * @param string $bind       Bind identifier without prefix.
     * @param mixed  $value      Value to be bound to the query.
     * @param string $columnName Column name associated with the bind.
     */
    public function __construct(string $bind, mixed $value, string $columnName = '')
    {
        $this->bind       = $bind;
        $this->bindValue  = $value;
        $this->columnName = $columnName;
        $this->prefixBind = ':';
    }

    /**
     * Create a new bind instance.
     *
     * This is a named constructor used to improve readability
     * when creating bind definitions fluently.
     *
     * @param string $bind       Bind identifier without prefix.
     * @param mixed  $value      Value to be bound to the query.
     * @param string $columnName Column name associated with the bind.
     * @return self
     */
    public static function set(string $bind, mixed $value, string $columnName = ''): self
    {
        return new Bind($bind, $value, $columnName);
    }

    /**
     * Set a custom prefix for the bind name.
     *
     * This is commonly used to avoid collisions when the same column
     * appears multiple times (e.g. multi-row inserts).
     *
     * @param string $prefix Prefix to prepend to the bind name.
     * @return $this
     */
    public function prefixBind(string $prefix): self
    {
        $this->prefixBind = $prefix;

        return $this;
    }

    /**
     * Set the bind identifier.
     *
     * @param string $bind Bind identifier without prefix.
     * @return $this
     */
    public function setBind(string $bind): self
    {
        $this->bind = $bind;

        return $this;
    }

    /**
     * Set the bind value.
     *
     * @param mixed $bindValue Value to associate with the bind.
     * @return $this
     */
    public function setValue(mixed $bindValue): self
    {
        $this->bindValue = $bindValue;

        return $this;
    }

    /**
     * Set the related column name.
     *
     * @param string $columnName Column name used in SQL expressions.
     * @return $this
     */
    public function setColumnName(string $columnName): self
    {
        $this->columnName = $columnName;

        return $this;
    }

    /**
     * Get the full bind placeholder.
     *
     * @return string Bind placeholder including prefix.
     */
    public function getBind(): string
    {
        return $this->prefixBind . $this->bind;
    }

    /**
     * Get the value associated with the bind.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->bindValue;
    }

    /**
     * Get the related column name.
     *
     * @return string
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * Determine whether a column name is defined.
     *
     * @return bool True if a column name is set.
     */
    public function hasColumName(): bool
    {
        return '' !== $this->columnName;
    }

    /**
     * Mark the bind as a column reference.
     *
     * This sets the column name equal to the bind identifier,
     * commonly used in UPDATE and INSERT statements.
     *
     * @return $this
     */
    public function markAsColumn(): self
    {
        $this->columnName = $this->bind;

        return $this;
    }

    /**
     * Determine whether the bind identifier is empty.
     *
     * @return bool True if the bind name is empty.
     */
    public function hasBind(): bool
    {
        return '' === $this->bind;
    }
}
