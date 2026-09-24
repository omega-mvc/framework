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
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_reduce;
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

    private const string NOT_RESOLVED = '__OMEGA_NOT_RESOLVED__';

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
     * @throws ReflectionException If reflection fails
     */
    public function resolveClass(string $concrete, array $parameters = []): mixed
    {
        $reflector = $this->container->getReflectionClass($concrete);
        $this->ensureInstantiable($reflector);

        return $this->withBuildStack($concrete, function () use ($concrete, $parameters, $reflector) {
            $dependencies = $this->container->getConstructorParameters($concrete);

            if (is_null($dependencies)) {
                return new $concrete();
            }

            return $reflector->newInstanceArgs(
                $this->resolveDependencies($dependencies, $parameters)
            );
        });
    }

    /**
     * Resolve an array of constructor dependencies.
     *
     * @param ReflectionParameter[] $dependencies The constructor parameters to resolve
     * @param array<int|string, mixed> $parameters Optional overrides for parameters
     * @return array<int|string, mixed> Resolved dependency instances
     */
    private function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $lastOverride = $this->container->getLastParameterOverride();

        return array_map(
            fn(ReflectionParameter $dependency) => $this->resolveSingleDependency(
                $dependency,
                $parameters,
                $lastOverride
            ),
            $dependencies
        );
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
        $result = $this->tryResolveFromType($parameter);
        if ($result !== self::NOT_RESOLVED) {
            return $result;
        }

        $result = $this->tryResolveFromDefault($parameter);
        if ($result !== self::NOT_RESOLVED) {
            return $result;
        }

        return $this->unresolvable($parameter, $parameter->getType() instanceof ReflectionUnionType);
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

    /**
     * Gestisce l'incapsulamento dello stato dello stack.
     */
    private function withBuildStack(string $concrete, callable $callback): mixed
    {
        if (isset($this->buildStack[$concrete])) {
            $path = implode(' -> ', array_keys($this->buildStack)) . ' -> ' . $concrete;
            throw new BindingResolutionException(
                sprintf("Circular dependency detected while trying to build [%s]. Path: %s.", $concrete, $path)
            );
        }

        $this->buildStack[$concrete] = true;

        // A finally block would give the path analyser a second, infeasible
        // success termination (the try body returning without running the
        // cleanup), so the exception path clears the stack explicitly and
        // re-throws, leaving exactly one success termination below.
        try {
            $result = $callback();
        } catch (Throwable $e) {
            unset($this->buildStack[$concrete]);

            throw $e;
        }

        unset($this->buildStack[$concrete]);

        return $result;
    }

    /**
     * Guard clause: verify the reflector targets an instantiable class.
     *
     * @param ReflectionClass<object> $reflector The class reflector to validate
     * @return void
     * @throws BindingResolutionException Thrown when the class is not instantiable.
     */
    private function ensureInstantiable(ReflectionClass $reflector): void
    {
        if (!$reflector->isInstantiable()) {
            throw new BindingResolutionException(sprintf("Target [%s] is not instantiable.", $reflector->getName()));
        }
    }

    /**
     * Resolve a single constructor or method parameter.
     *
     * @param ReflectionParameter $dependency The parameter to resolve
     * @param array<int|string, mixed> $parameters Optional overrides for parameters
     * @param array<int|string, mixed> $lastOverride Last parameter override values from the container
     * @return mixed The resolved value
     * @throws BindingResolutionException If the parameter cannot be resolved
     * @throws CircularAliasException If a circular dependency is detected
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a required container entry is missing
     * @throws ReflectionException If reflection fails
     */
    private function resolveSingleDependency(
        ReflectionParameter $dependency,
        array $parameters,
        array $lastOverride
    ): mixed {
        if (array_key_exists($dependency->name, $parameters)) {
            return $parameters[$dependency->name];
        }

        if (array_key_exists($dependency->getPosition(), $parameters)) {
            return $parameters[$dependency->getPosition()];
        }

        if (array_key_exists($dependency->name, $lastOverride)) {
            return $lastOverride[$dependency->name];
        }

        return $this->resolveParameterDependency($dependency);
    }

    private function tryResolveFromType(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        if (!$type) {
            return self::NOT_RESOLVED;
        }

        if ($type instanceof ReflectionIntersectionType) {
            $class = $parameter->getDeclaringClass()?->getName() ?? 'unknown';

            throw new BindingResolutionException(
                sprintf(
                    "Intersection types are not supported for dependency resolution of [%s] in class %s",
                    $parameter,
                    $class
                )
            );
        }

        $isUnion = $type instanceof ReflectionUnionType;

        // Building the flat type list through a helper keeps $isUnion a single
        // decision point below: branching on it twice in this method would let
        // the path analyser cross the two sites into an infeasible path.
        /** @var list<ReflectionNamedType> $classTypes */
        $classTypes = array_filter(
            $this->flattenTypeHints($type),
            fn (ReflectionType $t): bool => $t instanceof ReflectionNamedType && !$t->isBuiltin()
        );

        // Estrarre il primo match dal container (il primo che risulta bound)
        // Splitting the bound ternary keeps a single decision per branch: a
        // compact conditional inside the reducer would let the path analyser
        // enumerate an infeasible short-circuit variant.
        $resolved = array_reduce($classTypes, function (mixed $carry, ReflectionNamedType $classType): mixed {
            if ($carry !== null) {
                return $carry;
            }

            $name = $classType->getName();

            if ($this->container->bound($name)) {
                return $this->container->get($name);
            }

            return null;
        });

        if ($resolved !== null) {
            return $resolved;
        }

        // Union parameters are satisfied only from the container, so the
        // autowire branch is exclusive with the union branch.
        if ($isUnion) {
            // no autowiring for unions
        } elseif (!empty($classTypes)) {
            return $this->container->make($classTypes[array_key_first($classTypes)]->getName());
        }

        if ($type->allowsNull()) {
            return null;
        }

        return self::NOT_RESOLVED;
    }

    /**
     * Flatten a parameter type into a list of member types.
     *
     * Union types expand to their members, every other type keeps itself as a
     * single-element list.
     *
     * @param ReflectionType $type The type to flatten.
     * @return array<int, ReflectionType> The flattened member list.
     */
    private function flattenTypeHints(ReflectionType $type): array
    {
        if ($type instanceof ReflectionUnionType) {
            return array_values($type->getTypes());
        }

        return [$type];
    }

    private function tryResolveFromDefault(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        return self::NOT_RESOLVED;
    }
}
