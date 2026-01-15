<?php

/**
 * Part of Omega - Time Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Time\Exceptions;

/**
 * Exception thrown when trying to access a property that does not exist.
 *
 * This is typically raised by the `Now` class when accessing undefined properties
 * via __get().
 *
 * @category   Omega
 * @package    Time
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class PropertyNotExistException extends AbstractTimeException
{
    /**
     * Creates a new PropertyNotExistException instance.
     *
     * @param string $propertyName The name of the property that was attempted to access.
     * @return void
     */
    public function __construct(string $propertyName)
    {
        parent::__construct('Property `%s` not exists.', $propertyName);
    }
}
