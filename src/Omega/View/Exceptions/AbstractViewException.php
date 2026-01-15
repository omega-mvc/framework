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

use InvalidArgumentException;

use function sprintf;

/**
 * Base exception class for all View-related exceptions.
 *
 * Provides a formatted message constructor using `sprintf`.
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
abstract class AbstractViewException extends InvalidArgumentException implements ViewExceptionInterface
{
    /**
     * Constructs a new AbstractViewException.
     *
     * @param string $message The exception message with optional placeholders.
     * @param mixed  ...$args Optional arguments to replace placeholders in the message.
     */
    public function __construct(string $message, ...$args)
    {
        parent::__construct(sprintf($message, ...$args));
    }
}
