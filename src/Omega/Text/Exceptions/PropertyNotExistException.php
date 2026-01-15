<?php

/**
 * Part of Omega - Text Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Text\Exceptions;

/**
 * Exception thrown when attempting to access a property that does not exist on a Text object.
 *
 * @category   Omega
 * @package    Text
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class PropertyNotExistException extends AbstractTextException
{
    /**
     * Constructs a new PropertyNotExistException.
     *
     * @param string $propertyName The name of the missing property.
     */
    public function __construct(string $propertyName)
    {
        parent::__construct('Property `%s` not exist.', $propertyName);
    }
}
