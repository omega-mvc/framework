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

namespace Omega\Logging;

/**
 * Class AbstractLogger.
 *
 * This abstract class serves as a base implementation of the LoggerInterface. It provides common logging functionality
 * by including the LoggerTrait, which can be reused by concrete logger classes. Classes extending this abstract logger
 * must implement specific logging behavior as defined in the LoggerInterface.
 *
 * @category  Omega
 * @package   Logging
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
abstract class AbstractLogger implements LoggerInterface
{
    use LoggerTrait;
}
