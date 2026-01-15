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

use InvalidArgumentException;

use function sprintf;

/**
 * Base exception class for all Time-related exceptions.
 *
 * Extends the standard InvalidArgumentException and implements
 * TimeExceptionInterface to provide a common type for catching
 * all exceptions related to Time operations.
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
abstract class AbstractTimeException extends InvalidArgumentException implements TimeExceptionInterface
{
    /**
     * Construct a new Time exception with formatted message.
     *
     * @param string $message The exception message, can contain sprintf placeholders.
     * @param mixed  ...$args Arguments to replace placeholders in the message.
     * @return void
     */
    public function __construct(string $message, ...$args)
    {
        parent::__construct(sprintf($message, ...$args));
    }
}
