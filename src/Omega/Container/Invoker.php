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
use Closure;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

use function array_key_exists;
use function array_map;
use function array_shift;
use function call_user_func_array;
use function class_exists;
use function function_exists;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;
use function sprintf;

/**
 * Invoker class responsible for calling callables and injecting dependencies.
 *
 * This class supports closures, class methods, static methods, invokable objects,
 * and class names with an __invoke() method. Dependencies are automatically
 * resolved via the container and method/function reflection.
 *
 * @category  Omega
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final readonly class Invoker
{
    /**
     * Create a new Invoker instance.
     *
     * @param Container $container The container used to resolve dependencies
     */
    public function __construct(private Container $container)
    {
    }

    /**
     * Call the given callable and inject its dependencies.
     *
     * Supports closures, functions, static and instance methods, invokable classes.
     *
     * @param callable|object|array{0: object|string, 1: string}|string $callable The callable to invoke
     * @param array<int|string, mixed> $parameters Optional parameters to override dependencies
     * @return mixed The result of the callable execution
     * @throws BindingResolutionException If a dependency cannot be resolved
     * @throws CircularAliasException If a circular alias is detected during resolution
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a required entry is missing from the container
     * @throws ReflectionException If reflection fails on the callable
     */
    public function call(callable|object|array|string $callable, array $parameters = []): mixed
    {
        // Handle array callable [object, method] or [class, method]
        if (is_array($callable)) {
            return $this->callMethod(instance: $callable[0], method: $callable[1], parameters: $parameters);
        }

        // Deliberately separate guards: a compound `&&` condition would let
        // the path analyser enumerate an infeasible path that skips into the
        // invokable branch, so every reachable path is exercised instead.
        // The chain is exhaustive: there is no enumerated fall-through for a
        // non-array/non-string/non-closure value that ends at the final throw.
        if (is_string($callable)) {
            if (class_exists($callable)) {
                if (!method_exists($callable, '__invoke')) {
                    throw new BindingResolutionException(
                        sprintf('The class %s is not invokable.', $callable)
                    );
                }

                return $this->callMethod(instance: $callable, method: '__invoke', parameters: $parameters);
            }

            if (function_exists($callable)) {
                $reflector    = new ReflectionFunction($callable);
                $dependencies = $this->resolveFunctionDependencies($reflector, $parameters);

                return call_user_func_array($callable, $dependencies);
            }

            throw new BindingResolutionException(
                'Unable to call the given callable. Unsupported type.'
            );
        }

        // Handle closure / function
        if ($callable instanceof Closure) {
            $reflector    = new ReflectionFunction($callable);
            $dependencies = $this->resolveFunctionDependencies($reflector, $parameters);

            return call_user_func_array($callable, $dependencies);
        }

        // Handle object (invokable object)
        // @phpstan-ignore-next-line: only is_object proves `object` provenance to phpstan, and that guard would create a phantom coverage path
        if (method_exists($callable, '__invoke')) {
            $reflectionMethod = $this->container->getReflectionMethod($callable, '__invoke');
            $dependencies     = $this->resolveMethodDependencies($reflectionMethod, $parameters);

            // @phpstan-ignore-next-line: same (callable)|object narrowing constraint as the branch guard above
            return $reflectionMethod->invokeArgs($callable, $dependencies);
        }

        throw new BindingResolutionException(
            'Unable to call the given callable. Unsupported type.'
        );
    }

    /**
     * Call a method on a class or object and inject dependencies.
     *
     * @param object|string $instance The object instance or class name
     * @param string $method The method name to invoke
     * @param array<int|string, mixed> $parameters Optional parameters to override dependencies
     * @return mixed The result of the method invocation
     * @throws BindingResolutionException If a dependency cannot be resolved
     * @throws CircularAliasException If a circular alias is detected
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a required entry is missing from the container
     * @throws ReflectionException If method reflection fails
     */
    private function callMethod(object|string $instance, string $method, array $parameters = []): mixed
    {
        // resolve class name
        if (is_string($instance)) {
            $resolved = $this->container->get($instance);
            if (!is_object($resolved)) {
                throw new BindingResolutionException(
                    sprintf(
                        "Resolved class %s is not an object instance.",
                        $instance
                    )
                );
            }

            $instance = $resolved;
        }

        $reflector    = $this->container->getReflectionMethod($instance, $method);
        $dependencies = $this->resolveFunctionDependencies($reflector, $parameters);

        return $reflector->invokeArgs($instance, $dependencies);
    }

    /**
     * Resolve dependencies for a function or closure.
     *
     * @param ReflectionFunctionAbstract $reflection Reflection of the function/closure
     * @param array<int|string, mixed> $parameters Optional parameters to override dependencies
     * @return array<int, mixed> The resolved dependencies in order
     * @throws BindingResolutionException If a dependency cannot be resolved
     * @throws CircularAliasException If a circular alias is detected
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a required entry is missing from the container
     * @throws ReflectionException If parameter reflection fails
     */
    private function resolveFunctionDependencies(ReflectionFunctionAbstract $reflection, array $parameters = []): array
    {
        $resolved = array_map(
            fn(ReflectionParameter $parameter): mixed => $this->resolveParameter($parameter, $parameters),
            $reflection->getParameters()
        );

        return array_merge($resolved, array_values($parameters));
    }

    /**
     * Resolve dependencies for a method call.
     *
     * @param ReflectionMethod $method The reflection of the method
     * @param array<int|string, mixed> $parameters Optional parameters to override dependencies
     * @return array<int, mixed> The resolved dependencies in order
     * @throws BindingResolutionException If a dependency cannot be resolved
     * @throws CircularAliasException If a circular alias is detected
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a required entry is missing from the container
     * @throws ReflectionException If parameter reflection fails
     */
    private function resolveMethodDependencies(
        ReflectionMethod $method,
        array $parameters = []
    ): array {
        return array_map(
            fn (ReflectionParameter $parameter) => $this->resolveParameter($parameter, $parameters),
            $method->getParameters()
        );
    }

    /**
     * Resolve a single parameter from overrides or via the container.
     *
     * @param array<int|string, mixed> $parameters Optional parameters to override dependencies
     * @return mixed The resolved parameter value
     */
    private function resolveParameter(ReflectionParameter $parameter, array &$parameters): mixed
    {
        $name = $parameter->getName();
        $pos  = $parameter->getPosition();

        if (array_key_exists($name, $parameters)) {
            $value = $parameters[$name];
            unset($parameters[$name]);
            return $value;
        }

        if (array_key_exists($pos, $parameters)) {
            $value = $parameters[$pos];
            unset($parameters[$pos]);
            return $value;
        }

        $type = $parameter->getType();

        // Deliberately separate guards: a compound `&&` condition would let
        // the path analyser enumerate infeasible short-circuit variants, so
        // each decision point keeps exactly one boolean expression. The
        // branches are mutually exclusive, so no predicate is re-evaluated
        // on a different code path.
        if ($name === 'container') {
            if ($type === null) {
                return $this->container;
            }

            if ($type instanceof ReflectionNamedType) {
                if ('self' === $type->getName()) {
                    return $this->container;
                }

                if (!$type->isBuiltin()) {
                    return $this->container->get($type->getName());
                }
            }
        } elseif ($type instanceof ReflectionNamedType) {
            if (!$type->isBuiltin()) {
                return $this->container->get($type->getName());
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if (!empty($parameters)) {
            return array_shift($parameters);
        }

        throw new BindingResolutionException(
            sprintf(
                "Unable to resolve dependency [%s] in callable",
                $parameter
            )
        );
    }
}
