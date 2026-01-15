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

namespace Omega\Database\Query\Join;

use Omega\Database\Query\InnerQuery;

use function implode;

/**
 * Abstract base class for SQL JOIN operations.
 *
 * This class provides a foundation for building different types of JOINs
 * (INNER, LEFT, RIGHT, FULL) between tables or subqueries. It handles
 * table references, column comparisons, and generates the raw SQL join string.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Query\Join
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
abstract class AbstractJoin
{
    /** @var string Name of the main/master table */
    protected string $mainTable = '';

    /** @var string Name of the reference table or alias */
    protected string $tableName = '';

    /** @var string Column name used for comparison (optional) */
    protected string $columnName = '';

    /** @var string[] List of column pairs used for comparison in the JOIN */
    protected array $compareColumn = [];

    /** @var string Raw JOIN SQL string */
    protected string $stringJoin = '';

    /** @var InnerQuery|null Subquery instance if joining a subquery */
    protected ?InnerQuery $subQuery = null;

    /**
     * Prevent direct instantiation; meant to be extended by specific JOIN types.
     */
    final public function __construct()
    {
    }

    /**
     * Set main table for the join using invoke.
     *
     * @param string $mainTable Name of the main/master table
     * @return self
     */
    public function __invoke(string $mainTable): self
    {
        $this->mainTable = $mainTable;

        return $this;
    }

    /**
     * Convert join instance to its SQL string representation.
     *
     * @return string Raw SQL JOIN string
     */
    public function __toString(): string
    {
        return $this->stringJoin();
    }

    /**
     * Factory method to create an instance and setup reference table and comparison.
     *
     * @param string|InnerQuery $refTable Table name or subquery to join
     * @param string            $id       Main table column to compare
     * @param string|null       $refId    Reference table column to compare (default: same as main table column)
     * @return AbstractJoin
     */
    public static function ref(InnerQuery|string $refTable, string $id, ?string $refId = null): AbstractJoin
    {
        $instance = new static();

        if ($refTable instanceof InnerQuery) {
            return $instance
                ->clause($refTable)
                ->compare($id, $refId);
        }

        return $instance
            ->tableRef($refTable)
            ->compare($id, $refId);
    }

    /**
     * Set the master/main table.
     *
     * @param string $mainTable Name of the master table
     * @return self
     */
    public function table(string $mainTable): self
    {
        $this->mainTable = $mainTable;

        return $this;
    }

    /**
     * Set subquery for the join and derive table alias.
     *
     * @param InnerQuery $select Subquery instance
     * @return self
     */
    public function clause(InnerQuery $select): self
    {
        $this->subQuery  = $select;
        $this->tableName = $select->getAlias();

        return $this;
    }

    /**
     * Set reference table for the join.
     *
     * @param string $refTable Name of the reference table
     * @return self
     */
    public function tableRef(string $refTable): self
    {
        $this->tableName = $refTable;

        return $this;
    }

    /**
     * Set both main table and reference table.
     *
     * @param string $mainTable Name of the master table
     * @param string $refTable  Name of the reference table
     * @return self
     */
    public function tableRelation(string $mainTable, string $refTable): self
    {
        $this->mainTable = $mainTable;
        $this->tableName = $refTable;

        return $this;
    }

    /**
     * Define the columns to compare for the join condition.
     *
     * @param string      $mainColumn    Column from the main table
     * @param string|null $compareColumn Column from the reference table (default: same as main column)
     * @return self
     */
    public function compare(string $mainColumn, ?string $compareColumn = null): self
    {
        $compareColumn ??= $mainColumn;

        $this->compareColumn[] = [
            $mainColumn, $compareColumn,
        ];

        return $this;
    }

    /**
     * Get the raw SQL string of the join.
     *
     * @return string Raw JOIN SQL
     */
    public function stringJoin(): string
    {
        return $this->joinBuilder();
    }

    /**
     * Build the raw join SQL string.
     *
     * @return string SQL string of the join
     */
    protected function joinBuilder(): string
    {
        return $this->stringJoin;
    }

    /**
     * Generate the ON clause for the JOIN from column comparisons.
     *
     * @return string SQL ON clause (e.g., a.id = b.id AND a.x = b.x)
     */
    protected function splitJoin(): string
    {
        $on = [];
        foreach ($this->compareColumn as $column) {
            $masterColumn  = $column[0];
            $compareColumn = $column[1];

            $on[] = "$this->mainTable.$masterColumn = $this->tableName.$compareColumn";
        }

        return implode(' AND ', $on);
    }

    /**
     * Get the table alias if subquery is used, otherwise return table name.
     *
     * @return string Table alias or table name
     */
    protected function getAlias(): string
    {
        return null === $this->subQuery ? $this->tableName : (string) $this->subQuery;
    }
}
