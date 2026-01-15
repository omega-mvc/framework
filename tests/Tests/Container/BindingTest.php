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

use Closure;
use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use stdClass;
use Tests\Container\Fixtures\AnotherService;
use Tests\Container\Fixtures\ConcreteService;
use Tests\Container\Fixtures\DependencyClass;
use Tests\Container\Fixtures\ServiceInterface;

/**
 * Tests the behavior of container bindings.
 *
 * This test class covers various scenarios related to binding abstractions to concrete implementations
 * within the dependency injection container. It verifies that:
 *
 * - Simple and closure-based bindings resolve correctly.
 * - Shared (singleton) and non-shared bindings behave as expected.
 * - Bindings can be overridden.
 * - Bindings respect alias resolution.
 * - The container correctly reports whether a binding exists (`has` and `bound`).
 * - All current bindings can be retrieved and are updated after overrides.
 * - Flush clears all bindings.
 *
 * Each test ensures that the container resolves dependencies correctly and consistently.
 *
 * @category  Tests
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(AliasException::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
class BindingTest extends AbstractTestContainer
{
    /**
     * Test bind basic concrete.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindBasicConcrete(): void
    {
        $container = $this->container;

        $container->bind(ServiceInterface::class, ConcreteService::class);
        $instance = $container->get(ServiceInterface::class);

        $this->assertInstanceOf(ConcreteService::class, $instance);
    }

    /**
     * Test bind closure.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindClosure(): void
    {
        $container = $this->container;
        $container->bind('foo', fn () => 'bar');

        $this->assertEquals('bar', $container->get('foo'));
    }

    /**
     * Test bind shared singleton.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindSharedSingleton(): void
    {
        $container = $this->container;
        $container->bind('foo', fn () => new stdClass(), true);

        $instance1 = $container->get('foo');
        $instance2 = $container->get('foo');

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test bind non-shared creates new.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindNonSharedCreatesNew(): void
    {
        $container = $this->container;
        $container->bind('foo', fn () => new stdClass());

        $instance1 = $container->make('foo');
        $instance2 = $container->make('foo');

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test bind override previous.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindOverridePrevious(): void
    {
        $container = $this->container;
        $container->bind('foo', fn () => 'bar');
        $container->bind('foo', fn () => 'baz');

        $this->assertEquals('baz', $container->get('foo'));
    }

    /**
     * Test bind string class.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindStringClass(): void
    {
        $container = $this->container;
        $container->bind(stdClass::class, stdClass::class);

        $this->assertInstanceOf(stdClass::class, $container->get(stdClass::class));
    }

    /**
     * Test bind concrete null defaults to abstract.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindConcreteNullDefaultsToAbstract(): void
    {
        $container = $this->container;
        $container->bind(stdClass::class);

        $this->assertInstanceOf(stdClass::class, $container->get(stdClass::class));
    }

    /**
     * Test bind multiple unrelated.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindMultipleUnrelated(): void
    {
        $container = $this->container;
        $container->bind('foo', stdClass::class);
        $container->bind('bar', AnotherService::class);

        $this->assertInstanceOf(stdClass::class, $container->get('foo'));
        $this->assertInstanceOf(AnotherService::class, $container->get('bar'));
    }

    /**
     * Test bind closure scalar return.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindClosureScalarReturn(): void
    {
        $this->container->bind('string_value', fn () => 'hello');
        $this->assertEquals('hello', $this->container->get('string_value'));

        $this->container->bind('int_value', fn () => 123);
        $this->assertEquals(123, $this->container->get('int_value'));
    }

    /**
     * Test bind closure with parameter.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindClosureWithParameter(): void
    {
        $this->container->bind('with_param', function (DependencyClass $dep) {
            return $dep;
        });

        $result = $this->container->get('with_param');
        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    /**
     * Test bind respect alias resolution.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBindRespectsAliasResolution(): void
    {
        $this->container->alias(ServiceInterface::class, 'my_interface_alias');
        $this->container->bind('my_interface_alias', AnotherService::class);

        // Even though we bound 'my_interface_alias', get(ServiceInterface::class) should resolve it
        $instance = $this->container->get(ServiceInterface::class);

        $this->assertInstanceOf(AnotherService::class, $instance);
    }

    /**
     * Test has returns true for existing binding.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function testHasReturnsTrueForExistingBinding(): void
    {
        $this->container->bind('foo', stdClass::class);

        $this->assertTrue($this->container->has('foo'));
    }

    /**
     * Test has returns false for missing binding.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function testHasReturnsFalseForMissingBinding(): void
    {
        $this->assertFalse($this->container->has('non-existent-binding'));
    }

    /**
     * Test bound mirrors has behavior.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function testBoundMirrorsHasBehavior(): void
    {
        $this->container->bind('foo', stdClass::class);

        $this->assertTrue($this->container->bound('foo'));
        $this->assertFalse($this->container->bound('non-existent'));
    }

    /**
     * Test bound respects alias resolution.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function testBoundRespectsAliasResolution(): void
    {
        $this->container->bind(ServiceInterface::class, ConcreteService::class);
        $this->container->alias(ServiceInterface::class, 'my_service_alias');

        $this->assertTrue($this->container->bound('my_service_alias'));
        $this->assertTrue($this->container->has('my_service_alias')); // Should also be true for consistency
    }

    /**
     * Test get binding returns all current bindings.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function testGetBindingsReturnsAllCurrentBindings(): void
    {
        $this->container->bind('foo', stdClass::class); // Explicitly non-shared
        $this->container->bind('bar', ConcreteService::class, true); // Explicitly shared

        $bindings = $this->container->getBindings();

        $this->assertArrayHasKey('foo', $bindings);
        $this->assertArrayHasKey('bar', $bindings);

        // Assert that concrete is always a Closure
        $this->assertInstanceOf(Closure::class, $bindings['foo']['concrete']);
        $this->assertInstanceOf(Closure::class, $bindings['bar']['concrete']);

        // Assert shared status
        $this->assertFalse($bindings['foo']['shared']);
        $this->assertTrue($bindings['bar']['shared']);
    }

    /**
     * Test get bindings update after override.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetBindingsUpdatedAfterOverride(): void
    {
        $this->container->bind('foo', stdClass::class);
        $this->container->bind('foo', ConcreteService::class); // Override

        $bindings = $this->container->getBindings();

        $this->assertArrayHasKey('foo', $bindings);
        $this->assertInstanceOf(Closure::class, $bindings['foo']['concrete']);

        // To further verify, resolve 'foo' and check its type
        $instance = $this->container->get('foo');
        $this->assertInstanceOf(ConcreteService::class, $instance);
    }

    /**
     * Test get bindings empty after flush.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function testGetBindingsEmptyAfterFlush(): void
    {
        $this->container->bind('foo', stdClass::class);
        $this->container->flush();

        $bindings = $this->container->getBindings();

        $this->assertEmpty($bindings);
    }
}
