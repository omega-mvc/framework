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

/**
 * This interface serves as a marker for all exceptions related to enumeration operations.
 *
 * It allows catching and handling enum-specific exceptions in a unified way.
 *
 * # Usage Example:
 * By implementing this interface in exceptions like BadInstantiationException and InvalidValueException,
 * it becomes possible to catch all enum-related errors using a single catch block:
 *
 * # Example:
 * ```php
 * try {
 *     $enum = AbstractEnum::from('invalid_value');
 * } catch (EnumExceptionInterface $e) {
 *     // Handle any enum-related exception
 *     echo $e->getMessage();
 * }
 * ```
 * This approach improves code maintainability and provides a clear structure for exception handling
 * within the enumeration system.
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
interface EnumExceptionInterface
{
}
