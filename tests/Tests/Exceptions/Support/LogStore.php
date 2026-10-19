<?php

/**
 * Part of Omega - Tests\Exceptions Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Exceptions\Support;

/**
 * LogStore holds reported exception messages shared across tests.
 *
 * The anonymous exception handler registered by the test suite cannot
 * reference a Pest test class, so this dedicated store keeps the reported
 * messages accessible from both the handler and the assertions.
 *
 * @category  Tests
 * @package   Exceptions
 * @subpackage Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class LogStore
{
    /** @var list<string> */
    private static array $logs = [];

    /**
     * Reset the collected log messages.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$logs = [];
    }

    /**
     * Append a message to the collected logs.
     *
     * @param string $message The message to store.
     * @return void
     */
    public static function push(string $message): void
    {
        self::$logs[] = $message;
    }

    /**
     * Return all collected log messages.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return self::$logs;
    }
}