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

namespace Omega\Database;

use PDOException;

/**
 * Defines the contract for database transaction handling.
 *
 * Implementations must provide transactional execution support,
 * including begin, commit, and rollback operations.
 *
 * @category  Omega
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface TransactionInterface
{
    /**
     * Execute a callable within a database transaction.
     *
     * The transaction is committed if the callable returns true,
     * otherwise it is rolled back.
     *
     * @param callable(): bool $callable The transactional operation to execute.
     * @return bool True if the transaction was committed, false if rolled back.
     */
    public function transaction(callable $callable): bool;

    /**
     * Begin a new database transaction.
     *
     * @return bool True if the transaction was successfully started.
     * @throws PDOException If the transaction cannot be started.
     */
    public function beginTransaction(): bool;

    /**
     * Commit the current database transaction.
     *
     * @return bool True if the transaction was successfully committed.
     * @throws PDOException If the commit operation fails.
     */
    public function endTransaction(): bool;

    /**
     * Roll back the current database transaction.
     *
     * @return bool True if the transaction was successfully rolled back.
     * @throws PDOException If the rollback operation fails.
     */
    public function cancelTransaction(): bool;
}
