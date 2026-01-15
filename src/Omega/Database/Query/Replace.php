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

namespace Omega\Database\Query;

use function array_chunk;
use function count;
use function implode;

/**
 * Builds and executes a REPLACE SQL query.
 *
 * This class extends {@see Insert} and reuses its value and binding logic,
 * overriding only the SQL compilation phase to generate a REPLACE statement.
 *
 * A REPLACE works similarly to an INSERT, but if a row with the same primary
 * or unique key already exists, it is deleted and replaced with the new row.
 *
 * Execution is handled by {@see AbstractExecute} through inheritance.
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
class Replace extends Insert
{
    /**
     * Compile the REPLACE SQL statement.
     *
     * This method builds a driver-specific REPLACE query using the
     * bound values inherited from {@see Insert}. Multiple rows are
     * supported and translated into grouped VALUES clauses.
     *
     * @return string The generated REPLACE SQL query.
     */
    protected function builder(): string
    {
        [$binds, , $columns] = $this->bindsDestructor();

        $stringsBinds = [];
        /** @var array<int, array<int, string>> $chunk */
        $chunk = array_chunk($binds, count($columns), true);
        foreach ($chunk as $group) {
            $stringsBinds[] = '(' . implode(', ', $group) . ')';
        }

        $stringBinds  = implode(', ', $stringsBinds);
        $stringColumn = implode(', ', $columns);

        return $this->query =
            "REPLACE INTO {$this->table} ({$stringColumn}) VALUES {$stringBinds}";
    }
}
