<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\Closure;

use ReflectionIntersectionType;
use ReflectionType;

final class EmptyIntersection extends ReflectionIntersectionType
{
    /** @return array<int, ReflectionType> */
    public function getTypes(): array
    {
        return [];
    }
}
