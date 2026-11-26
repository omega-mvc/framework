<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\Closure;

use ReflectionType;

/** A reflection type that is neither named, union nor intersection. */
final class PlainReflectionType extends ReflectionType
{
}
