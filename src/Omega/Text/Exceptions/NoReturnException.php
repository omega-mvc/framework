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
 * Exception thrown when a text method fails to return a valid string or result.
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
class NoReturnException extends AbstractTextException
{
    /**
     * Constructs a new NoReturnException.
     *
     * @param string $method       The name of the method that did not return a value.
     * @param string $originalText The original string passed to the method.
     */
    public function __construct(string $method, string $originalText)
    {
        parent::__construct('The method %s called with %s did not return anything.', $method, $originalText);
    }
}
