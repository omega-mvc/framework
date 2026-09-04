<?php

/**
 * Part of Omega - Cache Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cache\Exceptions;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentExceptionInterface;

use function sprintf;

/**
 * Exception thrown when attempting to increment or modify a cache value
 * that is not of the expected integer type.
 *
 * @category   Omega
 * @package    Cache
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class InvalidValueIncrementException extends InvalidArgumentException implements PsrInvalidArgumentExceptionInterface
{
    /**
     * Create a new InvalidValueIncrementException instance.
     *
     * @param string $key The cache key whose value is not an integer.
     */
    public function __construct(string $key)
    {
        parent::__construct(
            sprintf(
                'The value for the cache key "%s" must be an integer to be incremented.',
                $key
            )
        );
    }
}
