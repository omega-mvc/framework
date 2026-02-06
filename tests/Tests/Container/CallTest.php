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
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\DummyStaticClass;
use Tests\Container\Support\InvokableClass;

/**
 * Tests the container's ability to call functions, methods, and invokable classes.
 *
 * This test class verifies that the container can correctly invoke callables, including:
 *
 * - Anonymous functions.
 * - Object instance methods.
 * - Static class methods.
 * - Invokable classes.
 * - Callables requiring dependency injection from the container.
 * - Callables with custom parameters.
 * - Callables with unresolvable parameters (expecting exceptions).
 *
 * Each test ensures that the container resolves dependencies correctly,
 * injects them as needed, and throws appropriate exceptions when
 * a callable cannot be executed due to missing or unresolvable parameters.
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
class CallTest extends AbstractTestContainer
{
    /**
     * test call function.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testCallFunction(): void
    {
        $result = $this->container->call(function () {
            return 'called';
        });
        $this->assertEquals('called', $result);
    }

    /**
     * Test call class method.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testCallClassMethod(): void
    {
        $dummy = new class {
            public function foo(): string
            {
                return 'bar';
            }
        };

        $result = $this->container->call([$dummy, 'foo']);
        $this->assertEquals('bar', $result);
    }

    /**
     * Test call static method.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testCallStaticMethod(): void
    {
        $result = $this->container->call([DummyStaticClass::class, 'staticMethod']);
        $this->assertEquals('static called', $result);
    }

    /**
     * Test call injects dependencies
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testCallInjectsDependencies(): void
    {
        $result = $this->container->call(function (DependencyClass $dependency) {
            return $dependency;
        });
        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    /**
     * Test call with custom parameters.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testCallWithCustomParameters(): void
    {
        $result = $this->container->call(function (DependencyClass $dependency, string $name) {
            return [$dependency, $name];
        }, ['name' => 'test']);
        $this->assertInstanceOf(DependencyClass::class, $result[0]);
        $this->assertEquals('test', $result[1]);
    }

    /**
     * Test call resolves vie container.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testCallResolvesViaContainer(): void
    {
        $this->container->bind(DependencyClass::class, function () {
            return new DependencyClass();
        });

        $result = $this->container->call(function (DependencyClass $dependency) {
            return $dependency;
        });
        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    /**
     * Test call unresolvable parameter.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testCallUnresolvableParameter(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Unable to resolve dependency [Parameter #0 [ <required> $param ]] in callable');

        $this->container->call(function ($param) {
        });
    }

    /**
     * Test call invokable class.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testCallInvokableClass(): void
    {
        $result = $this->container->call(InvokableClass::class);

        $this->assertEquals('invoked', $result);
    }
}
