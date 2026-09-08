<?php

declare(strict_types=1);

namespace Omega\DocBlockGenerator\Parser\Closure;

use Omega\DocBlockGenerator\Parser\File\NamespaceResolver as ParseNamespace;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

use function array_unique;
use function array_values;
use function is_object;
use function strtolower;

final class NamespaceResolver
{
    /**
     * @return array<int, string>
     */
    public function resolve(ReflectionFunction $reflection): array
    {
        /** @var array<int, class-string> $neededClasses */
        $neededClasses = [];

        // Collect types actually used by the closure from reflection
        foreach ($reflection->getParameters() as $parameter) {
            $this->collectFromType($parameter->getType(), $neededClasses);
        }

        $this->collectFromType($reflection->getReturnType(), $neededClasses);

        foreach ($reflection->getStaticVariables() as $value) {
            if (true === is_object($value)) {
                $neededClasses[] = $value::class;
            }
        }

        // Get all class imports from the file
        $classParse = new ParseNamespace();
        $file = $reflection->getFileName();
        $fileImports = false === $file ? [] : $classParse->resolveClasses($file);

        // Filter file imports to only those that match needed classes (case-insensitive)
        $neededLower = array_map('strtolower', $neededClasses);
        $result = [];
        foreach ($fileImports as $import) {
            if (in_array(strtolower($import), $neededLower, true)) {
                $result[] = $import;
            }
        }

        // Also add any needed classes that weren't in file imports (e.g., same namespace)
        foreach ($neededClasses as $needed) {
            $neededLower = strtolower($needed);
            $found = false;
            foreach ($fileImports as $import) {
                if (strtolower($import) === $neededLower) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $result[] = $needed;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @param array<int, string> $classes
     * @param-out array<int, string> $classes
     */
    private function collectFromType(?ReflectionType $type, array &$classes): void
    {
        if (null === $type) {
            return;
        }

        if ($type instanceof ReflectionNamedType && false === $type->isBuiltin()) {
            $classes[] = $type->getName();

            return;
        }

        if (
            $type instanceof ReflectionUnionType
            || $type instanceof ReflectionIntersectionType
        ) {
            foreach ($type->getTypes() as $inner) {
                $this->collectFromType($inner, $classes);
            }
        }
    }
}
