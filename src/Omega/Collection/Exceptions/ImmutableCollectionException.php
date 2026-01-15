<?php

/**
 * Part of Omega - Collection Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Collection\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when attempting to modify an immutable collection.
 *
 * This exception is used to enforce immutability rules inside collection
 * implementations that are meant to be read-only. Any operation that tries
 * to change the internal state of such a collection should result in this
 * exception being raised.
 *
 * @category   Omega
 * @package    Collection
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class ImmutableCollectionException extends InvalidArgumentException
{
    /**
     * Create a new ImmutableCollectionException instance.
     *
     * @rturn void
     */
    public function __construct()
    {
        parent::__construct('Collection immutable can not be modify');
    }
}
