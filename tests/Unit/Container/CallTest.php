<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Resolver;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\DummyStaticClass;
use Tests\Container\Support\InvokableClass;
use Tests\TestCase;

#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Resolver::class)]
final class CallTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testCallsAnAnonymousFunction(): void
    {
        $result = $this->container->call(function () {
            return 'called';
        });

        $this->assertSame('called', $result);
    }

    public function testCallsAnObjectInstanceMethod(): void
    {
        $dummy = new class {
            public function foo(): string
            {
                return 'bar';
            }
        };

        $this->assertSame('bar', $this->container->call([$dummy, 'foo']));
    }

    public function testCallsAStaticMethod(): void
    {
        $this->assertSame('static called', $this->container->call([DummyStaticClass::class, 'staticMethod']));
    }

    public function testInjectsDependenciesIntoCallables(): void
    {
        $result = $this->container->call(function (DependencyClass $dependency) {
            return $dependency;
        });

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    public function testCallsACallableWithCustomParameters(): void
    {
        $result = $this->container->call(function (DependencyClass $dependency, string $name) {
            return [$dependency, $name];
        }, ['name' => 'test']);

        $this->assertIsArray($result);

        $this->assertInstanceOf(DependencyClass::class, $result[0]);
        $this->assertSame('test', $result[1]);
    }

    public function testResolvesCallableDependenciesThroughTheContainer(): void
    {
        $this->container->bind(DependencyClass::class, fn () => new DependencyClass());

        $result = $this->container->call(function (DependencyClass $dependency) {
            return $dependency;
        });

        $this->assertInstanceOf(DependencyClass::class, $result);
    }

    public function testThrowsWhenACallableParameterCannotBeResolved(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage(
            'Unable to resolve dependency [Parameter #0 [ <required> $param ]] in callable'
        );

        $this->container->call(function ($param): void {
        });
    }

    public function testCallsAnInvokableClass(): void
    {
        $this->assertSame('invoked', $this->container->call(InvokableClass::class));
    }
}
