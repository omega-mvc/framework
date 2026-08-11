<?php

/**
 * Part of Omega - Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Logging\Exception;

use Exception;

use function sprintf;

/**
 * Class UnknownDriverException.
 *
 * Thrown when attempting to resolve a log driver that is unknown or
 * has not been registered with the {@see \Omega\Logging\LoggingManager}.
 *
 * @category   Omega
 * @package    Logging
 * @subpackage Exception
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class UnknownDriverException extends Exception
{
    /**
     * Create a new UnknownDriverException instance.
     *
     * @param string $driverName The name of the log driver that could not be resolved.
     */
    public function __construct(string $driverName)
    {
        parent::__construct(
            sprintf(
                'The log driver "%s" could not be resolved or is not registered.',
                $driverName
            )
        );
    }
}
