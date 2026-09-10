<?php

/**
 * Part of Omega - Exceptions Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Exceptions;

use RuntimeException;

/**
 * Exception thrown when an attempt is made to access the application
 * before it has been initialized.
 *
 * Note:
 * This exception is primarily used for testing purposes within the
 * `app` helper and is not expected to occur during normal application
 * lifecycle. Its usage reflects a safeguard for uninitialized application access.
 *
 * @category  Omega
 * @package   Exceptions
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class ApplicationNotAvailableException extends RuntimeException
{
    /**
     * Initializes the exception with a fixed message indicating
     * that the application has not been started yet.
     */
    public function __construct()
    {
        parent::__construct('Application not start yet!');
    }
}
