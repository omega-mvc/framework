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
use Tests\Container\Support\DummyClass;

/**
 * Tests the container aliasing system.
 *
 * This test suite verifies that aliases correctly map identifiers,
 * support recursive resolution, respect binding precedence, propagate
 * shared instances, and properly detect invalid or circular alias definitions.
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
class AliasTest extends AbstractTestContainer
{
    /**
     * Test alias basic
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testAliasBasic(): void
    {
        $container = $this->container;
        $container->bind(self::class . 'Foo', fn () => 'foo');
        $container->alias(self::class . 'Foo', 'foo-alias');

        $this->assertEquals('foo', $container->get('foo-alias'));
    }

    /**
     * Test alias recursive resolution.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testAliasRecursiveResolution(): void
    {
        $container = $this->container;
        $container->bind('foo', fn () => 'bar');
        $container->alias('foo', 'alias1');
        $container->alias('alias1', 'alias2');

        $this->assertEquals('bar', $container->get('alias2'));
    }

    /**
     * Test alias shadow.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testAliasShadow(): void
    {
        $container = $this->container;
        $container->bind('foo', fn () => 'foo-instance');
        $container->bind('bar', fn () => 'bar-instance');
        $container->alias('foo', 'shadow');
        $container->alias('bar', 'shadow');

        $this->assertEquals('bar-instance', $container->get('shadow'));
    }

    /**
     * Test get alias.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function testAliasGetAlias(): void
    {
        $container = $this->container;
        $container->alias('foo', 'bar');

        $this->assertEquals('foo', $container->getAlias('bar'));
    }

    /**
     * Test alias used in bind.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testAliasUsedInBind(): void
    {
        $container = $this->container;
        $container->alias('foo', 'bar');
        $container->bind('bar', fn () => 'baz');

        $this->assertEquals('baz', $container->get('foo'));
    }

    /**
     * Test alias throws exception on self alias.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     */
    public function testAliasThrowsAliasExceptionOnSelfAlias(): void
    {
        $this->expectException(AliasException::class);
        $this->container->alias('foo', 'foo');
    }

    /**
     * Test alias throws circular exception on circular reference.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testAliasThrowsCircularAliasExceptionOnCircularReference(): void
    {
        $this->expectException(CircularAliasException::class);
        $container = $this->container;
        $container->alias('foo', 'bar');
        $container->alias('bar', 'foo');
        $container->get('foo');
    }

    /**
     * Test alias share binding.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testAliasSharedBinding(): void
    {
        // Bind as shared (singleton)
        $this->container->bind(DummyClass::class, null, true);
        $this->container->alias(DummyClass::class, 'dummy_alias');

        $instance1 = $this->container->get(DummyClass::class);
        $instance2 = $this->container->get('dummy_alias');

        $this->assertSame($instance1, $instance2);
    }
}
