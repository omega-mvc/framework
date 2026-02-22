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

/** @noinspection PhpDocSignatureInspection */

declare(strict_types=1);

namespace Omega\Database\Query\Traits;

use Omega\Database\Query\Select;

use function implode;

/**
 * Provides methods for handling subqueries in SQL query builders.
 *
 * This trait allows adding subquery conditions to WHERE clauses, supporting
 * common SQL subquery operators such as EXISTS, NOT EXISTS, IN, =, and LIKE.
 * It automatically merges the binds from the subquery into the main query.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Query\Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait SubQueryTrait
{
    /**
     * Add a raw subquery to the WHERE statement.
     *
     * @param string $clause Raw SQL clause or operator (e.g., "EXISTS", "column =")
     * @param Select $select The Select instance representing the subquery
     * @return self
     */
    public function whereClause(string $clause, Select $select): self
    {
        $binds         = (fn () => $this->{'binds'})->call($select);
        $this->where[] = implode(' ', [$clause, '(', (string) $select, ')']);
        foreach ($binds as $bind) {
            $this->binds[] = $bind;
        }

        return $this;
    }

    /**
     * Add a comparison condition with a subquery.
     *
     * Example: columnName operator (subquery)
     *
     * @param string $columnName Column name
     * @param string $operator   Comparison operator (e.g., '=', '>', '<')
     * @param Select $select     Subquery instance
     * @return self
     */
    public function whereCompare(string $columnName, string $operator, Select $select): self
    {
        return $this->whereClause($columnName . ' ' . $operator, $select);
    }

    /**
     * Add a WHERE EXISTS condition with a subquery.
     *
     * @param Select $select Subquery instance
     * @return self
     */
    public function whereExist(Select $select): self
    {
        return $this->whereClause('EXISTS', $select);
    }

    /**
     * Add a WHERE NOT EXISTS condition with a subquery.
     *
     * @param Select $select Subquery instance
     * @return self
     */
    public function whereNotExist(Select $select): self
    {
        return $this->whereClause('NOT EXISTS', $select);
    }

    /**
     * Add a WHERE equal condition using a subquery.
     *
     * Example: columnName = (subquery)
     *
     * @param string $columnName Column name
     * @param Select $select     Subquery instance
     * @return self
     */
    public function whereEqual(string $columnName, Select $select): self
    {
        return $this->whereClause($columnName . ' =', $select);
    }

    /**
     * Add a WHERE LIKE condition using a subquery.
     *
     * Example: columnName LIKE (subquery)
     *
     * @param string $columnName Column name
     * @param Select $select     Subquery instance
     * @return self
     */
    public function whereLike(string $columnName, Select $select): self
    {
        return $this->whereClause($columnName . ' LIKE', $select);
    }

    /**
     * Add a WHERE IN condition using a subquery.
     *
     * Example: columnName IN (subquery)
     *
     * @param string $columnName Column name
     * @param Select $select     Subquery instance
     * @return self
     */
    public function whereIn(string $columnName, Select $select): self
    {
        return $this->whereClause($columnName . ' IN', $select);
    }
}
