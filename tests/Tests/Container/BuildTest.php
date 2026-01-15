<?php

/**
 * Part of Omega - Tests\Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use stdClass;
use Tests\Container\Fixtures\CircularA;
use Tests\Container\Fixtures\ClassWithMissingDependency;
use Tests\Container\Fixtures\ClassWithNullableUnionTypeConstructor;
use Tests\Container\Fixtures\ClassWithUnionTypeConstructor;
use Tests\Container\Fixtures\Dependant;
use Tests\Container\Fixtures\Dependency;
use Tests\Container\Fixtures\DependencyClass;
use Tests\Container\Fixtures\PrivateConstructorClass;
use Tests\Container\Fixtures\ScalarConstructorClass;
use Tests\Container\Fixtures\Service;
use Tests\Container\Fixtures\TypedConstructorClass;
use Tests\Container\Fixtures\UnionDependencyOne;
use Tests\Container\Fixtures\UnionDependencyTwo;

/**
 * Tests the container's ability to construct objects.
 *
 * This test class verifies that the container can correctly build instances
 * of classes, taking into account various constructor scenarios, including:
 *
 * - Classes with no dependencies.
 * - Classes with dependencies that must be resolved from the container.
 * - Classes requiring custom parameters.
 * - Classes constructed from closures.
 * - Classes with missing dependencies (expecting exceptions).
 * - Circular dependencies (expecting exceptions).
 * - Typed and union type constructors.
 * - Nullable constructor parameters.
 * - Scalar constructor parameters (expecting exceptions).
 * - Private constructors (expecting exceptions).
 *
 * Each test ensures that the container resolves dependencies correctly,
 * respects binding rules, and throws appropriate exceptions when
 * construction is not possible.
 *
 * @category  Tests
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
class BuildTest extends AbstractTestContainer
{
    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container->flush();
    }

    /**
     * Test build constructs class.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildConstructsClass(): void
    {
        $container = $this->container;
        $instance  = $container->build(stdClass::class);

        $this->assertInstanceOf(stdClass::class, $instance);
    }

    /**
     * Test build with dependencies.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildWithDependencies(): void
    {
        $container = $this->container;
        $instance  = $container->build(Dependant::class);

        $this->assertInstanceOf(Dependant::class, $instance);
        $this->assertInstanceOf(Dependency::class, $instance->dep);
    }

    /**
     * Test build with custom parameters.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildWithCustomParameters(): void
    {
        $container = $this->container;
        $instance  = $container->build(Service::class, ['value' => 'custom']);

        $this->assertEquals('custom', $instance->value);
    }

    /**
     * Test build from closure.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildFromClosure(): void
    {
        $container = $this->container;
        $result    = $container->build(fn () => 'foo');

        $this->assertEquals('foo', $result);
    }

    /**
     * Test build missing dependency.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildMissingDependency(): void
    {
        $this->expectException(BindingResolutionException::class);

        $container = $this->container;
        $container->build(ClassWithMissingDependency::class);
    }

    /**
     * Test build circular dependency.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildCircularDependency(): void
    {
        $this->expectException(BindingResolutionException::class);

        $container = $this->container;
        $container->build(CircularA::class);
    }

    /**
     * Test build typed constructor.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildTypedConstructor(): void
    {
        $instance = $this->container->build(TypedConstructorClass::class);

        $this->assertInstanceOf(TypedConstructorClass::class, $instance);
        $this->assertInstanceOf(DependencyClass::class, $instance->dep);
    }

    /**
     * Test build resolves first union type.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function buildResolvesFirstUnionType(): void
    {
        $this->container->bind(UnionDependencyOne::class, fn () => new UnionDependencyOne());
        $instance = $this->container->build(ClassWithUnionTypeConstructor::class);
        $this->assertInstanceOf(UnionDependencyOne::class, $instance->dependency);
    }

    /**
     * Test build resolves second union type.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function buildResolvesSecondUnionType(): void
    {
        $this->container->bind(UnionDependencyTwo::class, fn () => new UnionDependencyTwo());
        $instance = $this->container->build(ClassWithUnionTypeConstructor::class);
        $this->assertInstanceOf(UnionDependencyTwo::class, $instance->dependency);
    }

    /**
     * Test build throws when no union type is bound.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildThrowsWhenNoUnionTypeIsBound(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->container->build(ClassWithUnionTypeConstructor::class);
    }

    /**
     * Test build nullable union type constructor.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildNullableUnionTypeConstructor(): void
    {
        // Resolve to null when no type is bound and the parameter is nullable
        $instance = $this->container->build(ClassWithNullableUnionTypeConstructor::class);
        $this->assertNull($instance->dependency);
    }

    /**
     * Test build with scalar param throws.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildWithScalarParamThrows(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->container->build(ScalarConstructorClass::class);
    }

    /**
     * Test build private constructor throws exception.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBuildPrivateConstructorThrowsException(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->container->build(PrivateConstructorClass::class);
    }
}
