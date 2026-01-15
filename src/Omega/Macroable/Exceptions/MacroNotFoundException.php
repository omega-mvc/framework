<?php

/**
 * Part of Omega - Macroable Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Macroable\Exceptions;

use InvalidArgumentException;

use function sprintf;

/**
 * Exception thrown when attempting to call a macro that has not been registered.
 *
 * This exception indicates that the requested method name does not exist
 * in the macro registry for the current class.
 *
 * @category   Omega
 * @package    Macroable
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class MacroNotFoundException extends InvalidArgumentException
{
    /**
     * Create a new MacroNotFoundException instance.
     *
     * @param string $methodName The name of the macro that was requested.
     * @return void
     */
    public function __construct(string $methodName)
    {
        parent::__construct(sprintf('Macro `%s` is not macro able.', $methodName));
    }
}
