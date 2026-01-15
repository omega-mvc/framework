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

use InvalidArgumentException;

use function sprintf;

/**
 * Base exception class for Text package.
 *
 * Provides a foundation for all exceptions related to text manipulation
 * and implements the TextExceptionInterface.
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
abstract class AbstractTextException extends InvalidArgumentException implements TextExceptionInterface
{
    /**
     * Constructs a new AbstractTextException with a formatted message.
     *
     * @param string $message The exception message format string.
     * @param mixed  ...$args Values to replace placeholders in the message.
     */
    public function __construct(string $message, ...$args)
    {
        parent::__construct(sprintf($message, ...$args));
    }
}
