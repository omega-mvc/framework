<?php

/**
 * Part of Omega - Logger Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Logging\Exceptions;

use Throwable;

/**
 * Marker interface for exceptions thrown by the Omega Logger package.
 *
 * Implementing this interface allows catching all logger-specific exceptions
 * in a unified way, while still preserving access to the standard Throwable
 * methods such as getMessage(), getCode(), and getTrace().
 *
 * This interface does not declare any methods itself; it serves purely as
 * a type hint for logger-related exceptions.
 *
 * @category   Omega
 * @package    Logging
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
interface LoggerExceptionInterface extends Throwable
{
}
