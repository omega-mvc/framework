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

namespace Omega\Collection;

use Omega\Collection\Exceptions\ImmutableCollectionException;

/**
 * Strict immutable collection implementation.
 *
 * This class extends {@see AbstractCollectionImmutable} and enforces true immutability
 * by preventing any modifications to the internal collection after construction.
 * Any attempt to modify the collection via array-access assignment or unsetting
 * will result in an {@see ImmutableCollectionException} being thrown.
 *
 * Ideal for use cases where data integrity is critical and accidental mutations
 * must be strictly avoided.
 *
 * @category  Omega
 * @package   Collection
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @extends AbstractCollectionImmutable<TKey, TValue>
 *
 * @property mixed $buah_1
 */
class CollectionImmutable extends AbstractCollectionImmutable
{
    /**
     * Throws an exception to prevent mutation via array access.
     *
     * @inheritdoc
     *
     * @throws ImmutableCollectionException Always thrown to enforce immutability.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new ImmutableCollectionException();
    }

    /**
     * Throws an exception to prevent unsetting values via array access.
     *
     * @inheritdoc
     *
     * @throws ImmutableCollectionException Always thrown to enforce immutability.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new ImmutableCollectionException();
    }
}
