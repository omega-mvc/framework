<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\Closure;

use ReflectionType;
use ReflectionUnionType;

/**
 * Compound reflection types used to exercise recursive collection with
 * zero members and with a single member. ReflectionUnionType and
 * ReflectionIntersectionType are not final, so they can be sub-classed.
 */
final class EmptyUnion extends ReflectionUnionType
{
    /** @return array<int, ReflectionType> */
    public function getTypes(): array
    {
        return [];
    }
}
