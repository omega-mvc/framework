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
use ReflectionProperty;
use stdClass;

/**
 * Tests the container's flush functionality.
 *
 * This test class verifies that the container properly resets its internal state when `flush()` is called.
 * Specifically, it ensures that:
 *
 * - All bindings are removed.
 * - All resolved instances are cleared.
 * - All aliases are removed.
 * - The internal reflection cache is cleared.
 *
 * Each test confirms that after a flush, the container behaves as if it were newly initialized.
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
class FlushTest extends AbstractTestContainer
{
    /**
     * Test flush remove bindings.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function testFlushRemovesBindings(): void
    {
        $this->container->bind('foo', function () {
            return 'bar';
        });

        $this->assertTrue($this->container->bound('foo'));

        $this->container->flush();

        $this->assertFalse($this->container->bound('foo'));
    }

    /**
     * Test flush clears cache.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testFlushClearsCache(): void
    {
        $this->container->bind('foo', fn () => new stdClass(), true);

        $instance1 = $this->container->get('foo');
        $instance2 = $this->container->get('foo');
        $this->assertSame($instance1, $instance2);

        $this->container->flush();

        $this->container->bind('foo', fn () => new stdClass(), true);
        $instance3 = $this->container->get('foo');

        $this->assertNotSame($instance1, $instance3);
    }

    /**
     * Test flush clears alias.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testFlushClearsAlias(): void
    {
        $this->container->bind(stdClass::class);
        $this->container->alias(stdClass::class, 'foo');
        $this->assertInstanceOf(stdClass::class, $this->container->get('foo'));

        $this->container->flush();

        $this->expectException(EntryNotFoundException::class);
        $this->container->get('foo');
    }

    /**
     * Test flush resets container.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @noinspection PhpExpressionResultUnusedInspection
     */
    public function testFlushResetsContainer(): void
    {
        // Set up the container with bindings, instances, and aliases
        $this->container->bind('foo', fn () => new stdClass(), true);
        $this->container->get('foo'); // Resolve to create an instance
        $this->container->alias('foo', 'bar');

        $this->container->flush();

        // Assert that all relevant internal properties are now empty
        $bindings = new ReflectionProperty($this->container, 'bindings');
        $bindings->setAccessible(true);
        $this->assertEmpty($bindings->getValue($this->container));

        $instances = new ReflectionProperty($this->container, 'instances');
        $instances->setAccessible(true);
        $this->assertEmpty($instances->getValue($this->container));

        $aliases = new ReflectionProperty($this->container, 'aliases');
        $aliases->setAccessible(true);
        $this->assertEmpty($aliases->getValue($this->container));

        $reflectionCache = new ReflectionProperty($this->container, 'reflectionCache');
        $reflectionCache->setAccessible(true);
        $this->assertEmpty($reflectionCache->getValue($this->container));
    }
}
