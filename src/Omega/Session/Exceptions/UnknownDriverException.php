<?php

/**
 * Part of Omega - Session Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Session\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when attempting to resolve a session storage driver
 * that is unknown or has not been registered.
 *
 * @category   Omega
 * @package    Session
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class UnknownDriverException extends RuntimeException
{
    public function __construct(string $driverName)
    {
        parent::__construct(
            sprintf('The session storage driver "%s" could not be resolved or is not registered.', $driverName),
        );
    }
}
