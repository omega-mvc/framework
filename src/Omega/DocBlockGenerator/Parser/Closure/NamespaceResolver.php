<?php

declare(strict_types=1);

namespace Omega\DocBlockGenerator\Parser\Closure;

use Omega\DocBlockGenerator\Parser\File\NamespaceResolver as ParseNamespace;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function array_walk;
use function in_array;
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
        $parameters = $reflection->getParameters();
        array_walk(
            $parameters,
            function (ReflectionParameter &$parameter) use (&$neededClasses): void {
                $this->collectFromType($parameter->getType(), $neededClasses);
            },
        );

        $this->collectFromType($reflection->getReturnType(), $neededClasses);

        /** @var array<int, object> $staticObjects */
        $staticObjects = array_filter(
            $reflection->getStaticVariables(),
            static fn (mixed $value): bool => is_object($value),
        );

        $neededClasses = array_merge(
            $neededClasses,
            array_map(static fn (object $value): string => $value::class, $staticObjects),
        );

        // Get all class imports from the file
        $file       = $reflection->getFileName();
        $fileImports = false === $file ? [] : (new ParseNamespace())->resolveClasses($file);
        $neededLower = array_map('strtolower', $neededClasses);
        $importLower = array_map('strtolower', $fileImports);

        // Filter file imports to only those that match needed classes (case-insensitive)
        $result = array_merge(
            array_filter(
                $fileImports,
                static fn (string $import): bool => in_array(strtolower($import), $neededLower, true),
            ),
            array_filter(
                $neededClasses,
                static fn (string $needed): bool => !in_array(strtolower($needed), $importLower, true),
            ),
        );

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

        $innerTypes = match (true) {
            $type instanceof ReflectionUnionType, $type instanceof ReflectionIntersectionType => $type->getTypes(),
            default => [],
        };

        array_walk(
            $innerTypes,
            function (ReflectionType &$inner) use (&$classes): void {
                $this->collectFromType($inner, $classes);
            },
        );
    }
}
