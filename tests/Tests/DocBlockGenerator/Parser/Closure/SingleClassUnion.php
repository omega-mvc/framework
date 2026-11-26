<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\Closure;

use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use Tests\DocBlockGenerator\Fixtures\UnionA;

final class SingleClassUnion extends ReflectionUnionType
{
    /** @return array<int, ReflectionNamedType|ReflectionIntersectionType> */
    public function getTypes(): array
    {
        $fn   = static fn (UnionA $a): UnionA => $a;
        $type = (new ReflectionFunction($fn))->getParameters()[0]->getType();

        return $type instanceof ReflectionNamedType ? [$type] : [];
    }
}
