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

use Omega\Database\Query\Traits\ConditionTrait;
use Omega\Database\Query\Traits\SubQueryTrait;

/**
 * Represents a WHERE clause builder for query conditions.
 *
 * This class collects filters, bindings, and logical conditions (AND/OR)
 * to be later merged into a query builder instance.
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
class Where
{
    use ConditionTrait;
    use SubQueryTrait;

    /** @var string Table name associated with the WHERE conditions. */
    private string $table;

    /**
     * Optional subquery reference.
     *
     * This property is mainly used to assist static analysis tools
     * (e.g. PHPStan) and is automatically skipped in runtime logic.
     *
     * @var InnerQuery|null
     */
    private ?InnerQuery $subQuery = null;

    /** @var Bind[] Collection of bind objects used for parameter binding. */
    private array $binds = [];

    /** @var string[] Raw WHERE clause fragments. */
    private array $where = [];

    /** @var array<string, string> Single-level filters mapped by column name. */
    private array $filters = [];

    /**
     * Logical strict mode flag.
     *
     * When true, conditions are combined using AND,
     * otherwise OR is used.
     *
     * @var bool
     */
    private bool $strictMode = true;

    /**
     * Create a new WHERE clause builder.
     *
     * @param string $tableName The table name used to prefix column references.
     */
    public function __construct(string $tableName)
    {
        $this->table = $tableName;
    }

    /**
     * Retrieve the internal WHERE state.
     *
     * Returns raw data used by the query builder, including
     * bindings, conditions, filters, and strict mode flag.
     *
     * @return array<string, Bind[]|string[]|array<string, string>|bool> {
     *     @type Bind[]               $binds    Bound parameters.
     *     @type string[]             $where    Raw WHERE fragments.
     *     @type array<string,string> $filters  Column filters.
     *     @type bool                 $isStrict Logical strict mode flag.
     * }
     */
    public function get(): array
    {
        return [
            'binds'    => $this->binds,
            'where'    => $this->where,
            'filters'  => $this->filters,
            'isStrict' => $this->strictMode,
        ];
    }

    /**
     * Reset all stored conditions and bindings.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->binds      = [];
        $this->where      = [];
        $this->filters    = [];
        $this->strictMode = true;
    }

    /**
     * Determine whether the WHERE clause is empty.
     *
     * A WHERE instance is considered empty when it contains
     * no bindings, conditions, or filters and is in strict mode.
     *
     * @return bool True if no conditions are defined.
     */
    public function isEmpty(): bool
    {
        return [] === $this->binds
            && [] === $this->where
            && [] === $this->filters
            && true === $this->strictMode;
    }
}
