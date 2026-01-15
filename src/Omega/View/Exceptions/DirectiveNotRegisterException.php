<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\View\Exceptions;

/**
 * Exception thrown when a directive is used but has not been registered.
 *
 * Indicates an attempt to use an unknown or unregistered directive.
 *
 * @category   Omega
 * @package    View
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class DirectiveNotRegisterException extends AbstractViewException
{
    /**
     * Constructs a new DirectiveNotRegisterException.
     *
     * @param string $name The name of the directive that is not registered.
     */
    public function __construct(string $name)
    {
        parent::__construct('Directive "%s" is not registered.', $name);
    }
}
