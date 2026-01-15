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

/** @noinspection PhpFullyQualifiedNameUsageInspection */

declare(strict_types=1);

namespace Omega\Database\Query;

use function trim;

/**
 * Represents a table reference or a subquery used inside SQL statements.
 *
 * An InnerQuery can wrap a Select query to be used as a subquery with an alias,
 * or simply represent a plain table name. It is commonly used in JOIN clauses
 * and other contexts where a table or subquery is required.
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
final readonly class InnerQuery implements \Stringable
{
    /**
     * Create a new InnerQuery instance.
     *
     * If a Select instance is provided, the InnerQuery represents a subquery.
     * Otherwise, it represents a plain table reference.
     *
     * @param Select|null $select Select query used as subquery.
     * @param string      $table  Table name or alias.
     */
    public function __construct(
        private ?Select $select = null,
        private string $table = ''
    ) {
    }

    /**
     * Determine whether this instance represents a subquery.
     *
     * @return bool True if a Select instance is defined.
     */
    public function isSubQuery(): bool
    {
        return null !== $this->select;
    }

    /**
     * Get the table alias or table name.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return $this->table;
    }

    /**
     * Get bind parameters from the inner Select query.
     *
     * @return Bind[] List of bind objects used by the subquery.
     */
    public function getBind(): array
    {
        return $this->select->getBinds();
    }

    /**
     * Convert the inner query to its SQL representation.
     *
     * If this instance wraps a subquery, it will be rendered as
     * "(SELECT ...) AS alias". Otherwise, only the table name is returned.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->isSubQuery()
            ? '(' . trim((string) $this->select) . ') AS ' . $this->getAlias()
            : $this->getAlias();
    }
}
