<?php

/**
 * Part of Omega - Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Container;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_values;
use function implode;
use function is_null;
use function sprintf;

/**
 * Resolver class for resolving class dependencies automatically.
 *
 * This class is responsible for instantiating classes with constructor
 * dependencies, handling circular dependencies, and resolving parameters
 * from the container or defaults.
 *
 * @category  Omega
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class Resolver
{
    /** @var array<string, bool> Stack of currently building classes to detect circular dependencies */
    private array $buildStack = [];

    /**
     * Create a new Resolver instance.
     *
     * @param Container $container The container used to resolve dependencies
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Instantiate a concrete instance of the given class type.
     *
     * @param string $concrete The class name to instantiate
     * @param array<int|string, mixed> $parameters Optional parameters to override constructor arguments
     * @return mixed The instantiated class with resolved dependencies
     * @throws BindingResolutionException If class is not instantiable or a dependency is unresolvable
     * @throws CircularAliasException If a circular dependency is detected
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a required container entry is missing
     * @throws ReflectionException If reflection fails
     */
    public function resolveClass(string $concrete, array $parameters = []): mixed
    {
        $reflector = $this->container->getReflectionClass($concrete);

        if (false === $reflector->isInstantiable()) {
            throw new BindingResolutionException(
                sprintf(
                    "Target [%s] is not instantiable.",
                    $concrete
                )
            );
        }

        if (isset($this->buildStack[$concrete])) {
            $path = implode(' -> ', array_keys($this->buildStack)) . ' -> ' . $concrete;
            throw new BindingResolutionException(
                sprintf(
                    "Circular dependency detected while trying to build [%s]. Path: %s.",
                    $concrete,
                    $path
                )
            );
        }

        $this->buildStack[$concrete] = true;

        try {
            $dependencies = $this->container->getConstructorParameters($concrete);

            if (is_null($dependencies)) {
                return new $concrete();
            }

            $instances = $this->resolveDependencies($dependencies, $parameters);

            return $reflector->newInstanceArgs($instances);
        } finally {
            unset($this->buildStack[$concrete]);
        }
    }

    /**
    /**
     * Resolve an array of constructor dependencies.
     *
     * @param ReflectionParameter[] $dependencies The constructor parameters to resolve
     * @param array<int|string, mixed> $parameters Optional overrides for parameters
     * @return array Resolved dependency instances
     * @throws BindingResolutionException If a dependency is unresolvable
     * @throws CircularAliasException If a circular dependency is detected
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a required container entry is missing
     * @throws ReflectionException If reflection fails
     */
    private function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $name = $dependency->name;

            if (array_key_exists($name, $parameters)) {
                $results[] = $parameters[$name];
                continue;
            }

            if (array_key_exists($dependency->getPosition(), $parameters)) {
                $results[] = $parameters[$dependency->getPosition()];
                continue;
            }

            $override = $this->container->getLastParameterOverride();
            if (array_key_exists($name, $override)) {
                $results[] = $override[$name];
                continue;
            }

            $results[] = $this->resolveParameterDependency($dependency);
        }

        return $results;
    }

    /**
     * Resolve a single constructor or method parameter.
     *
     * @param ReflectionParameter $parameter The parameter to resolve
     * @return mixed The resolved value
     * @throws BindingResolutionException If the parameter cannot be resolved
     * @throws CircularAliasException If a circular dependency is detected
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a required container entry is missing
     * @throws ReflectionException If reflection fails
     */
    public function resolveParameterDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (null === $type) {
            return $this->resolveUnTypedParameter($parameter);
        }

        if ($type instanceof ReflectionIntersectionType) {
            throw new BindingResolutionException(
                sprintf(
                    "Intersection types are not supported for dependency resolution of [%s] in class %s",
                    $parameter,
                    $parameter->getDeclaringClass()->getName()
                )
            );
        }

        $isUnion    = $type instanceof ReflectionUnionType;
        $types      = $isUnion ? $type->getTypes() : [$type];
        $classTypes = array_filter(
            $types,
            fn ($t): bool => $t instanceof ReflectionNamedType && false === $t->isBuiltin()
        );

        foreach ($classTypes as $classType) {
            $name = $classType->getName();
            if ($this->container->bound($name)) {
                return $this->container->get($name);
            }
        }

        if (false === $isUnion && false === empty($classTypes)) {
            $firstClass = array_values($classTypes)[0];

            return $this->container->make($firstClass->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($type->allowsNull()) {
            return null;
        }

        return $this->unresolvable($parameter, $isUnion);
    }

    /**
     * Resolve a parameter without type hint.
     *
     * @param ReflectionParameter $parameter The parameter to resolve
     * @return mixed The resolved value or default
     * @throws BindingResolutionException If the parameter cannot be resolved
     */
    private function resolveUnTypedParameter(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return $this->unresolvable($parameter);
    }

    /**
     * Throw exception for an unresolvable parameter.
     *
     * @phpstan-return never
     * @param ReflectionParameter $parameter The parameter that cannot be resolved
     * @param bool $isUnion Whether the parameter is a union type
     * @throws BindingResolutionException Always
     */
    private function unresolvable(ReflectionParameter $parameter, bool $isUnion = false): void
    {
        $class     = $parameter->getDeclaringClass();
        $className = $class ? $class->getName() : 'unknown';
        $message   = $isUnion
            ? 'none of the types in the union are bound in the container'
            : 'the dependency is not bound and cannot be autowired';

        throw new BindingResolutionException(
            sprintf(
                "Unresolvable dependency resolving [%s] in class %s: %s",
                $parameter,
                $className,
                $message
            )
        );
    }
}
