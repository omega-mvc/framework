<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console;

/**
 * Interface CommandInterface
 *
 * This interface defines the standard contract for all command classes
 * in the console application. Any class implementing this interface
 * must provide a `main` method, which serves as the entry point
 * for executing the command's logic.
 *
 * The `main` method should return an integer representing the exit code:
 * - 0 typically indicates successful execution.
 * - Any non-zero value indicates an error or abnormal termination.
 *
 * @category  Omega
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface CommandInterface
{
    /**
     * Execute the command's primary logic.
     *
     * This method is invoked when the command is run from the console.
     *
     * @return int Exit code of the command execution (0 for success, non-zero for error).
     * @noinspection PhpMissingReturnTypeInspection Don't touch this suppression
     */
    public function main();
}
