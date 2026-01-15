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

use Omega\Collection\Collection;

/**
 * Abstract base class for fetchable queries.
 *
 * Extends AbstractQuery and provides methods to retrieve query results
 * as a collection, a single row, or all rows. Handles parameter binding
 * and query execution via the PDO connection.
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
abstract class AbstractFetch extends AbstractQuery
{
    /**
     * Get query results as a Collection object.
     *
     * @return Collection<string|int, mixed>|null Returns a Collection containing all results,
     *                                           or null if no results are found.
     */
    public function get(): ?Collection
    {
        if (false === ($items = $this->all())) {
            $items = [];
        }

        return new Collection($items);
    }

    /**
     * Get a single row from the query result.
     *
     * Executes the query with bound parameters and fetches a single row.
     *
     * @return array<string, mixed> Returns the row as an associative array.
     *                              Returns an empty array if no row is found.
     */
    public function single(): array
    {
        $this->builder();

        $this->pdo->query($this->query);
        foreach ($this->binds as $bind) {
            if (!$bind->hasBind()) {
                $this->pdo->bind($bind->getBind(), $bind->getValue());
            }
        }
        $result = $this->pdo->single();

        return $result === false ? [] : $this->pdo->single();
    }

    /**
     * Get all rows from the query result.
     *
     * Executes the query with bound parameters and returns the full result set.
     *
     * @return array<string|int, mixed>|false Returns an array of rows, or false on failure.
     */
    public function all(): array|false
    {
        $this->builder();

        $this->pdo->query($this->query);
        foreach ($this->binds as $bind) {
            if (!$bind->hasBind()) {
                $this->pdo->bind($bind->getBind(), $bind->getValue());
            }
        }

        return $this->pdo->resultset();
    }
}
