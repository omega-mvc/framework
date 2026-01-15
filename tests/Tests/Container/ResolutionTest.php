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

use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use stdClass;
use Tests\Container\Fixtures\DeepA;
use Tests\Container\Fixtures\DeepB;
use Tests\Container\Fixtures\DeepC;
use Tests\Container\Fixtures\DependencyClass;
use Tests\Container\Fixtures\Service;
use Tests\Container\Fixtures\UnresolvableClass;

/**
 * Class ResolutionTest
 *
 * This test class verifies the container's resolution behavior for shared and non-shared
 * instances, closures, aliases, and recursive dependencies. It ensures that `get` returns
 * cached shared instances while `make` creates fresh instances. It also tests parameter
 * overrides, exception handling for unresolvable dependencies, and correct resolution
 * through aliases.
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
class ResolutionTest extends AbstractTestContainer
{
    /**
     * Test get resolves shared.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetResolvesShared(): void
    {
        $this->container->bind(DependencyClass::class, null, true);

        $instance1 = $this->container->get(DependencyClass::class);
        $instance2 = $this->container->get(DependencyClass::class);

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test get not found.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetNotFound(): void
    {
        $this->expectException(EntryNotFoundException::class);

        $this->container->get('non-existent-class');
    }

    /**
     * Test make fresh instance.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testMakeFreshInstance(): void
    {
        $instance1 = $this->container->make(stdClass::class);
        $instance2 = $this->container->make(stdClass::class);

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test make with parameters.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testMakeWithParameters(): void
    {
        $instance = $this->container->make(Service::class, ['value' => 'custom']);

        $this->assertInstanceOf(Service::class, $instance);
        $this->assertEquals('custom', $instance->value);
    }

    /**
     * Test get closure.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @noinspection PhpUnusedParameterInspection
     */
    public function testGetClosure(): void
    {
        $this->container->bind('test-closure', function ($container) {
            return 'resolved from closure';
        });

        $result = $this->container->get('test-closure');
        $this->assertEquals('resolved from closure', $result);
    }

    /**
     * Test make closure.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @noinspection PhpUnusedParameterInspection
     */
    public function testMakeClosure(): void
    {
        $this->container->bind('test-closure', function ($container) {
            return 'resolved from closure';
        });

        $result = $this->container->make('test-closure');
        $this->assertEquals('resolved from closure', $result);
    }

    /**
     * Test get via alias.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetViaAlias(): void
    {
        $this->container->bind('dependency', DependencyClass::class);
        $this->container->alias('dependency', 'alias');

        $this->assertInstanceOf(
            DependencyClass::class,
            $this->container->get('alias')
        );
    }

    /**
     * Test make via alias.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testMakeViaAlias(): void
    {
        $this->container->bind(DependencyClass::class); // Make sure it's non-shared
        $this->container->alias(DependencyClass::class, 'dependency_alias');

        $instance = $this->container->make('dependency_alias');
        $this->assertInstanceOf(DependencyClass::class, $instance);
    }

    /**
     * Test get singleton cached.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetSingletonCached(): void
    {
        $counter = 0;

        $this->container->bind(DependencyClass::class, function () use (&$counter) {
            $counter++;

            return new DependencyClass();
        }, true);

        $this->container->get(DependencyClass::class);
        $this->container->get(DependencyClass::class);

        $this->assertEquals(1, $counter);
    }

    /**
     * Test get resolves recursive dependencies.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetResolvesRecursiveDependencies(): void
    {
        $instance = $this->container->get(DeepA::class);

        $this->assertInstanceOf(DeepA::class, $instance);
        $this->assertInstanceOf(DeepB::class, $instance->b);
        $this->assertInstanceOf(DeepC::class, $instance->b->c);
    }


    /**
     * Test make resolves recursive dependencies.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testMakeResolvesRecursiveDependencies(): void
    {
        $instance = $this->container->make(DeepA::class);

        $this->assertInstanceOf(DeepA::class, $instance);
        $this->assertInstanceOf(DeepB::class, $instance->b);
        $this->assertInstanceOf(DeepC::class, $instance->b->c);
    }

    /**
     * Test make unresolvable dependency.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testMakeUnresolvableDependency(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->container->make(UnresolvableClass::class);
    }
}
