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

namespace Omega\Database\Exceptions;

use InvalidArgumentException;

/**
 * InvalidConfigurationException
 *
 * Exception thrown when a database connection configuration is invalid or incomplete.
 *
 * This exception is raised by the Connection class when:
 * - Required configuration keys (like host, database, or path) are missing.
 * - Provided configuration values are not compatible with the selected driver.
 * - A DSN string cannot be built due to incorrect or missing settings.
 *
 * Example usage:
 * ```
 * try {
 *     $connection = new Connection($configs);
 * } catch (InvalidConfigurationException $e) {
 *     echo "Database configuration error: " . $e->getMessage();
 * }
 * ```
 *
 * @category   Omega
 * @package    Database
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class InvalidConfigurationException extends InvalidArgumentException
{
}
