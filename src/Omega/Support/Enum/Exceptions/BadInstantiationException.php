<?php

/**
 * Part of Omega MVC - Support Package
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Support\Enum\Exceptions;

use BadMethodCallException;

/**
 * This exception is thrown when attempting to instantiate an abstract class. Since AbstractEnum is not
 * meant to be instantiated directly, this exception prevents incorrect usage.
 *
 * @category   Omega
 * @package    Support
 * @subpackage Enum\Excptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class BadInstantiationException extends BadMethodCallException implements EnumExceptionInterface
{
}
