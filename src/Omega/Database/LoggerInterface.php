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

/**
 * Defines the contract for query execution logging.
 *
 * Implementations are responsible for collecting and exposing
 * execution timing and diagnostic information for database queries.
 *
 * @category  Omega
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface LoggerInterface
{
    /**
     * Clear all stored query execution logs.
     *
     * @return void
     */
    public function flushLogs(): void;

    /**
     * Retrieve the collected query execution logs.
     *
     * Each log entry contains start time, end time, and execution duration
     * expressed in milliseconds.
     *
     * @return array<int, array<string, float|string|null>> Collected query logs.
     */
    public function getLogs(): array;
}
