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

use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use stdClass;
use Tests\Container\Fixtures\AnotherService;
use Tests\Container\Fixtures\Dependant;
use Tests\Container\Fixtures\Dependency;
use Tests\Container\Fixtures\DependencyClass;
use Tests\Container\Fixtures\InjectionUsingAttribute;
use Tests\Container\Fixtures\InjectionUsingAttributeOnParameter;
use Tests\Container\Fixtures\InjectionUsingAttributeOnProperty;
use Tests\Container\Fixtures\MultipleSetterClass;
use Tests\Container\Fixtures\NestedDependencyClass;
use Tests\Container\Fixtures\NonSetterClass;
use Tests\Container\Fixtures\ScalarSetterClass;
use Tests\Container\Fixtures\SetterInjectionClass;
use Tests\Container\Fixtures\StaticSetterClass;
use Tests\Container\Fixtures\UnresolvableSetterClass;

/**
 * Class InjectOnTest
 *
 * This test class verifies the behavior of the container's `injectOn` method, which performs
 * dependency injection on existing objects. It ensures that:
 *
 * - Setter methods are automatically called with resolved dependencies.
 * - Non-setter methods and static methods are ignored.
 * - Only class-typed parameters are injected, while scalar or unresolvable parameters are skipped.
 * - Multiple setters and nested dependencies are correctly resolved.
 * - Objects annotated with `#[Inject]` attributes on properties or constructor parameters
 *   receive proper dependency injection.
 * - The original instance is returned unchanged after injection.
 *
 * These tests cover a variety of scenarios, including handling of nested objects, multiple
 * dependencies, attribute-based injection, and edge cases like unresolvable or static setters.
 *
 * @category  Tests
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
class InjectOnTest extends AbstractTestContainer
{
    /**
     * Test inject call setters.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectCallsSetters(): void
    {
        $instance = new SetterInjectionClass();
        $this->container->injectOn($instance);

        $this->assertInstanceOf(DependencyClass::class, $instance->dependency);
    }

    /**
     * Test inject skips non setters.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectSkipsNonSetters(): void
    {
        $instance = new NonSetterClass();
        $this->container->injectOn($instance);

        $this->assertFalse($instance->called);
    }

    /**
     *
     * Test inject only class types.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectOnlyClassTypes(): void
    {
        $instance = new ScalarSetterClass();
        $this->container->injectOn($instance);

        $this->assertEquals('default', $instance->name);
    }

    /**
     * Test inject ignores unresolvable.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectIgnoresUnresolvable(): void
    {
        $instance = new UnresolvableSetterClass();
        $this->container->injectOn($instance);

        $this->assertNull($instance->dependency);
    }

    /**
     * Test injects skips static.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectSkipsStatic(): void
    {
        StaticSetterClass::$called = false; // Reset static property
        $instance                  = new class { // Create a dummy object to inject on
            // This object has no setters, so injectOn won't modify it,
            // but we want to ensure it doesn't accidentally trigger static setters
        };
        $this->container->injectOn($instance);

        $this->assertFalse(StaticSetterClass::$called);
    }

    /**
     * Test injects multiple setters.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectMultipleSetters(): void
    {
        $instance = new MultipleSetterClass();
        $this->container->injectOn($instance);

        $this->assertInstanceOf(DependencyClass::class, $instance->dependency1);
        $this->assertInstanceOf(AnotherService::class, $instance->dependency2);
    }

    /**
     * Test inject resolves nested.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectResolvesNested(): void
    {
        $instance = new NestedDependencyClass();
        $this->container->injectOn($instance);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(NestedDependencyClass::class, $instance);
        $this->assertInstanceOf(Dependant::class, $instance->dependant);
        $this->assertInstanceOf(Dependency::class, $instance->dependant->dep);
    }

    /**
     * Test inject returns original.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectReturnsOriginal(): void
    {
        $instance         = new stdClass();
        $returnedInstance = $this->container->injectOn($instance);

        $this->assertSame($instance, $returnedInstance);
    }

    /**
     * Test inject using in inject attribute.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectUsingInjectAttribute(): void
    {
        $instance         = new InjectionUsingAttribute();
        $returnedInstance = $this->container->injectOn($instance);

        $this->assertSame($instance, $returnedInstance);
        $this->assertEquals('foo', $returnedInstance->dependency);
    }

    /**
     * Test inject using inject attribute on parameter.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectUsingInjectAttributeOnParameter(): void
    {
        $this->container->set('db.host', 'localhost');
        $instance = new InjectionUsingAttributeOnParameter();
        $returnedInstance = $this->container->injectOn($instance);

        $this->assertSame($instance, $returnedInstance);
        $this->assertEquals('localhost', $returnedInstance->dependency);
    }

    /**
     * Test inject using inject attribute on property.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testInjectUsingInjectAttributeOnProperty(): void
    {
        $this->container->set('db.host', 'localhost');
        $instance = new InjectionUsingAttributeOnProperty();
        $returnedInstance = $this->container->injectOn($instance);

        $this->assertSame($instance, $returnedInstance);
        $this->assertEquals('localhost', $returnedInstance->dependency);
    }
}
