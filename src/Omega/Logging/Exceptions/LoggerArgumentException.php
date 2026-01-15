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

use InvalidArgumentException;

/**
 * Class LogArgumentException.
 *
 * This exception is thrown when an invalid argument is passed to a logging-related method or function.
 * It extends the built-in InvalidArgumentException and is specific to logging operations within the Omega framework.
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
class LoggerArgumentException extends InvalidArgumentException implements LoggerExceptionInterface
{
}
