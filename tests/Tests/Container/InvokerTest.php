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

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Invoker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\Container\Support\CallableClass;
use Tests\Container\Support\CallableNoDeps;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\InvokableInvokeClass;
use TypeError;

/**
 * Class InvokerTest
 *
 * This test class verifies the behavior of the Invoker, ensuring it can correctly
 * call closures, class methods, static methods, and invokable classes while
 * resolving dependencies via the container. It also tests parameter overrides
 * and proper exception handling for unsupported callables or invokable classes
 * missing an __invoke method.
 *
 * @category  Tests
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Container::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Invoker::class)]
final class InvokerTest extends TestCase
{
    /** @var Container Container instance used for resolving dependencies */
    private Container $container;

    /** @var Invoker Invoker instance that wraps callable invocation with dependency injection */
    private Invoker $invoker;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Container reale
        $this->container = new Container();
        $this->invoker   = new Invoker($this->container);
    }

    /**
     * Test it can invoke a closure with dependencies.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanInvokeAClosureWithDependencies(): void
    {
        $result = $this->invoker->call(function (DependencyClass $d) {
            return $d;
        });

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    /**
     * Test it can invoke a class method with dependencies.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanInvokeAClassMethodWithDependencies(): void
    {
        $result = $this->invoker->call([CallableClass::class, 'someMethod']);

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    /**
     * Test it can invoke a static class method with dependencies.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanInvokeAStaticClassMethodWithDependencies(): void
    {
        $result = $this->invoker->call([CallableClass::class, 'staticMethod']);

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    /**
     * Test it can invoke an invokable class.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanInvokeAnInvokableClass(): void
    {
        $result = $this->invoker->call(InvokableInvokeClass::class);

        $this->assertSame('invoked', $result);

        $instance = $this->container->get(InvokableInvokeClass::class);
        $this->assertInstanceOf(DependencyClass::class, $instance->dep);
    }

    /**
     * Test it overrides parameters correctly.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItOverridesParametersCorrectly(): void
    {
        $override = new DependencyClass();

        $result = $this->invoker->call(
            fn (DependencyClass $d) => $d,
            ['d' => $override]
        );

        $this->assertSame($override, $result);
    }

    /**
     * Test it throws exception for unsupported callable type.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItThrowsExceptionForUnsupportedCallableType(): void
    {
        $this->expectException(TypeError::class);

        /** @noinspection PhpStrictTypeCheckingInspection */
        $this->invoker->call(123);
    }

    /**
     * Test it throws exception if invokable class no invoke method.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItThrowsExceptionIfInvokableClassHasNoInvokeMethod(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->invoker->call(CallableNoDeps::class);
    }
}
