<?php

/**
 * Part of Omega - Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version   2.0.0
 */

namespace Omega\Database;

use PDOException;

/**
 * Defines the contract for a database connection.
 *
 * A Connection implementation is responsible for preparing and executing
 * SQL statements, binding parameters, fetching results, managing transactions,
 * and collecting execution logs.
 *
 * @category  Omega
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version   2.0.0
 */
interface ConnectionInterface extends LoggerInterface, TransactionInterface
{
    /**
     * Return the current connection instance.
     *
     * Provided for backward compatibility with code that accessed the
     * connection through a shared instance; it does not implement a real
     * singleton and simply returns `$this`.
     *
     * @return self Returns the current connection instance.
     */
    public function getInstance(): self;

    /**
     * Prepare an SQL statement for execution.
     *
     * @param string $query The SQL query string to prepare.
     * @return self Returns the current connection instance for method chaining.
     */
    public function query(string $query): self;

    /**
     * Bind a value to a parameter in the prepared statement.
     *
     * The parameter may be a named or positional placeholder. The value is
     * safely bound to prevent SQL injection.
     *
     * @param string|int $param The parameter identifier or placeholder.
     * @param mixed      $value The value to bind to the parameter.
     * @param int|null   $type  Optional PDO parameter type.
     * @return self Returns the current connection instance for method chaining.
     */
    public function bind(
        string|int $param,
        mixed $value,
        int|null $type = null
    ): self;

    /**
     * Execute the prepared SQL statement.
     *
     * @return bool True on successful execution, false otherwise.
     * @throws PDOException If the execution fails at the driver level.
     */
    public function execute(): bool;

    /**
     * Fetch all rows from the executed statement.
     *
     * @return array<int, array<string, mixed>>|false An array of result rows, or false if no results are available.
     */
    public function resultset(): array|false;

    /**
     * Fetch a single row from the executed statement.
     *
     * @return array<string, mixed>|false The fetched row, or false if no result is available.
     */
    public function single(): array|false;

    /**
     * Get the number of affected rows from the last executed statement.
     *
     * @return int The number of rows inserted, updated, or deleted.
     */
    public function rowCount(): int;

    /**
     * Retrieve the ID of the last inserted row.
     *
     * @return string|false The last insert ID, or false if not supported.
     */
    public function lastInsertId(): string|false;
}
