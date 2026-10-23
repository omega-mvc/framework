<?php

declare(strict_types=1);

namespace Tests\Container;

use Closure;
use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Invoker;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use stdClass;
use Tests\Container\Support\CallableClass;
use Tests\Container\Support\CallableNoDeps;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\InvokableInvokeClass;
use Tests\TestCase;

#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Invoker::class)]
final class InvokerTest extends TestCase
{
    private Container $container;

    private Invoker $invoker;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->invoker   = new Invoker($this->container);
    }

    public function testInvokesAClosureWithDependencies(): void
    {
        $result = $this->invoker->call(function (DependencyClass $dep) {
            return $dep;
        });

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    public function testInvokesAClassMethodWithDependencies(): void
    {
        $result = $this->invoker->call([CallableClass::class, 'someMethod']);

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    public function testInvokesAStaticClassMethodWithDependencies(): void
    {
        $result = $this->invoker->call([CallableClass::class, 'staticMethod']);

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    public function testInvokesAMethodOnAnAlreadyInstantiatedObject(): void
    {
        $instance = new CallableClass();

        $result = $this->invoker->call([$instance, 'someMethod']);

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    public function testInvokesAnInvokableClass(): void
    {
        $result = $this->invoker->call(InvokableInvokeClass::class);

        $this->assertSame('invoked', $result);

        $instance = $this->container->get(InvokableInvokeClass::class);

        $this->assertInstanceOf(InvokableInvokeClass::class, $instance);

        $this->assertNotSame($instance, $instance->dep);
    }

    public function testOverridesParametersCorrectly(): void
    {
        $override = new DependencyClass();

        $result = $this->invoker->call(
            fn (DependencyClass $dep) => $dep,
            ['dep' => $override]
        );

        $this->assertSame($override, $result);
    }

    public function testThrowsForUnsupportedCallableTypes(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->invoker->call(new stdClass());
    }

    public function testThrowsWhenAnInvokableClassHasNoInvokeMethod(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->invoker->call(CallableNoDeps::class);
    }

    public function testInvokesAnInvokableObjectInstance(): void
    {
        $expected = new DependencyClass();
        $this->container->bind(DependencyClass::class, static fn () => $expected, true);

        $invokable = new InvokableInvokeClass();

        $result = $this->invoker->call($invokable);

        $this->assertSame('invoked', $result);
        $this->assertSame($expected, $invokable->dep);
    }

    public function testInjectsTheContainerIntoSelfTypedContainerParameters(): void
    {
        $subclass = new class extends Container {
            public function makeClosure(): Closure
            {
                return fn (self $container) => $container;
            }
        };

        $invoker = new Invoker($subclass);

        $this->assertSame($subclass, $invoker->call($subclass->makeClosure()));
    }

    public function testFallsBackToParameterDefaults(): void
    {
        $result = $this->invoker->call(fn (string $name = 'default') => $name);

        $this->assertSame('default', $result);
    }

    public function testConsumesLeftoverParametersPositionally(): void
    {
        $result = $this->invoker->call(fn (int $value) => 'value:' . $value, ['unmatched' => 7]);

        $this->assertSame('value:7', $result);
    }

    public function testAppendsUnmatchedParametersToTheInvocationArguments(): void
    {
        $result = $this->invoker->call(fn (mixed $first) => $first, ['first' => 1, 'second' => 2]);

        $this->assertSame(1, $result);
    }

    public function testOverridesParametersByPosition(): void
    {
        $override = new DependencyClass();

        $result = $this->invoker->call(
            fn (DependencyClass $dep) => $dep,
            [0 => $override]
        );

        $this->assertSame($override, $result);
    }

    public function testThrowsWhenAResolvedCallableClassIsNotAnObject(): void
    {
        $this->container->set('answer', 42);

        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Resolved class answer is not an object instance.');

        $this->invoker->call(['answer', 'anyMethod']);
    }

    public function testThrowsWhenAStringCallableIsNeitherAClassNorAFunction(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Unable to call the given callable. Unsupported type.');

        $this->invoker->call('undeclared_function_name');
    }

    public function testInvokesANamedGlobalFunctionWithDependencies(): void
    {
        $result = $this->invoker->call('Tests\Container\invokerTestFunction');

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    public function testInjectsTheContainerIntoAnUntypedContainerParameter(): void
    {
        // The untyped parameter is what makes the container fall back to the current instance.
        // @phpstan-ignore typeCoverage.paramTypeCoverage
        $result = $this->invoker->call(fn ($container) => $container);

        $this->assertSame($this->container, $result);
    }

    public function testResolvesAContainerNamedParameterWithAClassType(): void
    {
        $result = $this->invoker->call(fn (DependencyClass $container) => $container);

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    public function testFallsBackToTheDefaultForAContainerNamedBuiltInParameter(): void
    {
        $result = $this->invoker->call(fn (string $container = 'default-string') => $container);

        $this->assertSame('default-string', $result);
    }

    public function testThrowsWhenAParameterHasNoResolvableValue(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->invoker->call(fn (mixed $missing) => $missing);
    }

    public function testFallsBackToTheDefaultForABuiltInTypedParameter(): void
    {
        $result = $this->invoker->call(fn (int $num = 3) => $num);

        $this->assertSame(3, $result);
    }

    public function testConsumesLeftoverParametersForAnUntypedParameter(): void
    {
        // The untyped parameter is the subject of this test: leftovers are consumed for it.
        // @phpstan-ignore typeCoverage.paramTypeCoverage
        $result = $this->invoker->call(fn ($value) => $value, ['unmatched' => 7]);

        $this->assertSame(7, $result);
    }

    public function testFallsBackToTheDefaultForAContainerNamedUnionTypedParameter(): void
    {
        $result = $this->invoker->call(fn (DependencyClass|stdClass|null $container = null) => $container);

        $this->assertNull($result);
    }
}

/**
 * Named function used by the invoker function test above to prove that a
 * plain function-name string resolves its dependencies and is invoked.
 *
 * @param DependencyClass $dependency The injected dependency
 * @return DependencyClass The very same dependency passed through
 */
function invokerTestFunction(DependencyClass $dependency): DependencyClass
{
    return $dependency;
}
