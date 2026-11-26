<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\Closure;

use ReflectionNamedType;

/** A named type that is explicitly builtin. */
final class BuiltinNamedType extends ReflectionNamedType
{
    public function getName(): string
    {
        return 'int';
    }

    public function isBuiltin(): bool
    {
        return true;
    }
}
