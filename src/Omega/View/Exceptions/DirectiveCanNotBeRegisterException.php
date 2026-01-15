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
 * Exception thrown when trying to register a directive that has already been registered.
 *
 * Indicates that the given directive name is already in use by another component or template.
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
class DirectiveCanNotBeRegisterException extends AbstractViewException
{
    /**
     * Constructs a new DirectiveCanNotBeRegisterException.
     *
     * @param string $name   The name of the directive that cannot be registered.
     * @param string $useBy  The component or template that has already registered this directive.
     */
    public function __construct(string $name, string $useBy)
    {
        parent::__construct('Directive "%s" cannot be used; it has already been used in "%s".', $name, $useBy);
    }
}
