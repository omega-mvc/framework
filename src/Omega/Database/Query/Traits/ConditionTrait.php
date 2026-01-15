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
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Database\Query\Traits;

use Omega\Database\Query\Bind;

use function implode;
use function str_replace;

/**
 * Provides common SQL condition methods for query building.
 *
 * This trait is designed to be used in query builder classes (e.g., Select, Update, Delete).
 * It handles common SQL conditions such as equal, like, between, in, and generic comparisons,
 * while managing PDO bind parameters automatically.
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
trait ConditionTrait
{
    /**
     * Add an 'equal' condition to the query.
     *
     * @param string               $bind  Column or bind name
     * @param bool|int|string|null $value Value to compare
     * @return self
     */
    public function equal(string $bind, bool|int|string|null $value): self
    {
        $this->compare($bind, '=', $value);

        return $this;
    }

    /**
     * Add a 'LIKE' condition to the query.
     *
     * @param string               $bind  Column or bind name
     * @param bool|int|string|null $value Value to compare
     * @return self
     */
    public function like(string $bind, bool|int|string|null $value): self
    {
        $this->compare($bind, 'LIKE', $value);

        return $this;
    }

    /**
     * Add a raw WHERE condition to the query.
     *
     * @param string                 $whereCondition Raw WHERE condition (e.g., "column = :bind")
     * @param array<int, array|Bind> $binder         List of binds or Bind objects
     * @return self
     */
    public function where(string $whereCondition, array $binder = []): self
    {
        $this->where[] = $whereCondition;

        foreach ($binder as $bind) {
            if ($bind instanceof Bind) {
                $this->binds[] = $bind;
                continue;
            }
            $this->binds[] = Bind::set($bind[0], $bind[1])->prefixBind('');
        }

        return $this;
    }

    /**
     * Add a 'BETWEEN' condition to the query.
     *
     * @param string $columnName Column name
     * @param int    $value_1    Start value
     * @param int    $value_2    End value
     * @return self
     */
    public function between(string $columnName, int $value_1, int $value_2): self
    {
        $tableName = null === $this->subQuery ? $this->table : $this->subQuery->getAlias();

        $this->where("({$tableName}.{$columnName} BETWEEN :b_start AND :b_end)");

        $this->binds[] = Bind::set('b_start', $value_1);
        $this->binds[] = Bind::set('b_end', $value_2);

        return $this;
    }

    /**
     * Add an 'IN' condition to the query.
     *
     * @param string                                  $columnName Column name
     * @param array<int|string, string|int|bool|null> $value      Values for the IN condition
     * @return self
     */
    public function in(string $columnName, array $value): self
    {
        $binds  = [];
        $binder = [];
        foreach ($value as $key => $bind) {
            $binds[]  = ":in_$key";
            $binder[] = [":in_$key", $bind];
        }
        $bindString = implode(', ', $binds);
        $tableName  = null === $this->subQuery ? $this->table : $this->subQuery->getAlias();

        $this->where("({$tableName}.{$columnName} IN ({$bindString}))", $binder);

        return $this;
    }

    /**
     * Add a generic comparison condition to the query.
     *
     * @param string               $bind       Column or bind name
     * @param string               $comparison SQL comparison operator (e.g., '=', '>', '<')
     * @param bool|int|string|null $value      Value to compare
     * @param bool                 $bindValue Optional, whether to bind the value directly
     * @return self
     */
    public function compare(
        string $bind,
        string $comparison,
        bool|int|string|null $value,
        bool $bindValue = false
    ): self {
        $escapeBind           = str_replace('.', '__', $bind);
        $this->binds[]        = Bind::set($escapeBind, $value);
        $this->filters[$bind] = [
            'value'      => $value,
            'comparison' => $comparison,
            'bind'       => $escapeBind,
            $bindValue,
        ];

        return $this;
    }

    /**
     * Set the strict mode for the query.
     *
     * True means combining conditions with AND, false means OR.
     *
     * @param bool $strict
     * @return self
     */
    public function strictMode(bool $strict): self
    {
        $this->strictMode = $strict;

        return $this;
    }
}
