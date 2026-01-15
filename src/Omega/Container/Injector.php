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

use Omega\Container\Attribute\Inject;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

use function array_key_exists;
use function count;
use function is_array;

/**
 * Injector class responsible for injecting dependencies into objects.
 *
 * This class uses the #[Inject] attribute to inject dependencies into public
 * methods and properties of an object. Dependencies are resolved via the
 * container or the resolver.
 *
 * @category  Omega
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class Injector
{
    /** @var Resolver|null Resolver instance used for parameter resolution */
    private ?Resolver $resolver;

    /**
     * Create a new Injector instance.
     *
     * @param Container $container The container used to resolve dependencies
     */
    public function __construct(private readonly Container $container)
    {
        $this->resolver = new Resolver($container);
    }

    /**
     * Inject dependencies into an existing object instance.
     *
     * This method will inject dependencies into all public methods and properties
     * that have the #[Inject] attribute.
     *
     * @param object $instance The object instance to perform injection upon
     * @return object The same instance with injected dependencies
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a required dependency cannot be found
     * @throws ReflectionException If reflection fails on the object
     */
    public function inject(object $instance): object
    {
        $reflector = $this->container->getReflectionClass($instance::class);

        $this->injectMethods($instance, $reflector);
        $this->injectProperties($instance, $reflector);

        return $instance;
    }

    /**
     * Inject dependencies into public methods annotated with #[Inject].
     *
     * @param object $instance The object instance to inject
     * @param ReflectionClass<object> $reflector Reflection class of the object
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a dependency cannot be resolved
     * @throws ReflectionException If method reflection fails
     */
    private function injectMethods(object $instance, ReflectionClass $reflector): void
    {
        // Look for method with #[Inject] attribute
        foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isStatic()) {
                continue;
            }

            $injects    = [];
            $attributes = $method->getAttributes(Inject::class);
            if (0 === count($attributes)) {
                continue;
            }

            $method_injects = $attributes[0]->newInstance()->getName();
            if (is_array($method_injects)) {
                $injects = $method_injects;
            }

            if ($method->getNumberOfParameters() > 0) {
                $parameters = $method->getParameters();

                // Only inject if all parameters are type-hinted with classes
                $canInject = true;
                foreach ($parameters as $param) {
                    // Check for #[Inject] on the parameter itself
                    $paramAttributes = $param->getAttributes(Inject::class);
                    $hasParamInject  = false === empty($paramAttributes);
                    $hasMethodInject = isset($injects[$param->name]);

                    if ($hasParamInject || $hasMethodInject) {
                        continue;
                    }

                    $type = $param->getType();
                    if (!$type || ($type instanceof ReflectionNamedType && $type->isBuiltin())) {
                        $canInject = false;
                        break;
                    }
                }

                if ($canInject) {
                    try {
                        $dependencies = [];
                        foreach ($parameters as $param) {
                            $paramName = $param->getName();

                            $paramAttributes = $param->getAttributes(Inject::class);
                            if (false === empty($paramAttributes)) {
                                $paramInject    = $paramAttributes[0]->newInstance();
                                $abstract       = $paramInject->getName();
                                $dependencies[] = $this->container->get($abstract);
                                continue;
                            }

                            if (array_key_exists($paramName, $injects)) {
                                $dependencies[] = $injects[$paramName];
                                continue;
                            }

                            // Only resolve if not provided in the inject attribute.
                            $dependencies[] = $this->resolver->resolveParameterDependency($param);
                        }

                        $method->invokeArgs($instance, $dependencies);
                    } /** @noinspection PhpUnusedLocalVariableInspection */ catch (BindingResolutionException $e) {
                        // Suppress exception if injection fails,
                        // allowing other injections to proceed.
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Inject dependencies into public properties annotated with #[Inject].
     *
     * @param object $instance The object instance to inject
     * @param ReflectionClass<object> $reflector Reflection class of the object
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException If a dependency cannot be resolved
     * @throws ReflectionException If property reflection fails
     */
    private function injectProperties(object $instance, ReflectionClass $reflector): void
    {
        // Look for property with #[Inject] attribute
        foreach ($reflector->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes(Inject::class);
            if (0 === count($attributes)) {
                continue;
            }

            $propertyInject = $attributes[0]->newInstance();
            $abstract       = $propertyInject->getName();

            try {
                if (is_array($abstract)) { // This check should ideally be for string or mixed
                    continue;
                }

                $dependency = $this->container->get($abstract);
                $property->setValue($instance, $dependency);
            } /** @noinspection PhpUnusedLocalVariableInspection */ catch (BindingResolutionException $e) {
                // Suppress exception if injection fails,
                // allowing other injections to proceed.
                continue;
            }
        }
    }
}
