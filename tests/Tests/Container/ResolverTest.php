<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Resolver;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionFunction;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Tests\Container\Support\CircularA;
use Tests\Container\Support\DefaultValueConstructorClass;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\IntersectionTypeConstructorClass;
use Tests\Container\Support\TypedConstructorClass;
use Tests\TestCase;

#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Resolver::class)]
final class ResolverTest extends TestCase
{
    public function testResolvesAClassWithoutAConstructor(): void
    {
        $resolver = new Resolver(new Container());

        $this->assertInstanceOf(stdClass::class, $resolver->resolveClass(stdClass::class));
    }

    public function testResolvesAClassWithDependencies(): void
    {
        $resolver = new Resolver(new Container());

        $instance = $resolver->resolveClass(TypedConstructorClass::class);

        $this->assertInstanceOf(TypedConstructorClass::class, $instance);
        $this->assertNotSame($instance, $instance->dep);
    }

    public function testThrowsOnCircularDependencies(): void
    {
        $resolver = new Resolver(new Container());

        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $resolver->resolveClass(CircularA::class);
    }

    public function testResetsTheParameterOverridePathAfterAFailedMake(): void
    {
        $container = new Container();

        $thrown = null;

        try {
            $container->bind('error', function (): void {
                throw new RuntimeException('Fail');
            });

            $container->make('error');
        } catch (RuntimeException $exception) {
            $thrown = $exception;
        }

        $reflection = new ReflectionProperty($container, 'with');

        $this->assertEmpty($reflection->getValue($container));
        $this->assertInstanceOf(RuntimeException::class, $thrown);
    }

    public function testThrowsForIntersectionTypedDependencies(): void
    {
        $resolver = new Resolver(new Container());

        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Intersection types are not supported for dependency resolution');

        $resolver->resolveClass(IntersectionTypeConstructorClass::class);
    }

    public function testReportsUnknownDeclaringClassesForUnresolvableFunctionParameters(): void
    {
        $resolver  = new Resolver(new Container());
        $parameter = (new ReflectionFunction('strlen'))->getParameters()[0];

        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('in class unknown');

        $resolver->resolveParameterDependency($parameter);
    }

    public function testRefusesParametersWithoutTypeHintsAndAvailableDefaults(): void
    {
        $resolver  = new Resolver(new Container());
        // The missing type hint is the subject of this test.
        // @phpstan-ignore typeCoverage.paramTypeCoverage
        $untyped   = function ($value): void {
        };
        $parameter = (new ReflectionFunction($untyped))->getParameters()[0];

        $this->expectException(BindingResolutionException::class);

        $resolver->resolveParameterDependency($parameter);
    }

    public function testResolvesClassDependenciesFromParameterDefaults(): void
    {
        $resolver = new Resolver(new Container());

        $instance = $resolver->resolveClass(DefaultValueConstructorClass::class);

        $this->assertInstanceOf(DefaultValueConstructorClass::class, $instance);
        $this->assertSame(5, $instance->getCount());
    }

    public function testRespectsNamedParameterOverridesWhenResolvingAClass(): void
    {
        $resolver  = new Resolver(new Container());
        $override  = new DependencyClass();

        $instance = $resolver->resolveClass(TypedConstructorClass::class, ['dep' => $override]);

        $this->assertInstanceOf(TypedConstructorClass::class, $instance);
        $this->assertSame($override, $instance->dep);
    }

    public function testRespectsPositionalParameterOverridesWhenResolvingAClass(): void
    {
        $resolver  = new Resolver(new Container());
        $override  = new DependencyClass();

        $instance = $resolver->resolveClass(TypedConstructorClass::class, [0 => $override]);

        $this->assertInstanceOf(TypedConstructorClass::class, $instance);
        $this->assertSame($override, $instance->dep);
    }

    public function testAppliesTheActiveParameterOverrideToAClassBuiltInsideAFactory(): void
    {
        $container = new Container();
        $container->bind(TypedConstructorClass::class, function (Container $container): TypedConstructorClass {
            $instance = $container->build(TypedConstructorClass::class);

            if (!$instance instanceof TypedConstructorClass) {
                throw new RuntimeException('Expected a TypedConstructorClass instance.');
            }

            return $instance;
        });

        $override = new DependencyClass();
        $instance = $container->make(TypedConstructorClass::class, ['dep' => $override]);

        $this->assertInstanceOf(TypedConstructorClass::class, $instance);
        $this->assertSame($override, $instance->dep);
    }

    public function testReportsUnresolvableUnionTypedDependencies(): void
    {
        $resolver  = new Resolver(new Container());
        $union     = function (DependencyClass|CircularA $value): void {
        };
        $parameter = (new ReflectionFunction($union))->getParameters()[0];

        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('none of the types in the union are bound in the container');

        $resolver->resolveParameterDependency($parameter);
    }

    public function testResolvesASingleTypedParameterFromABoundValue(): void
    {
        $container = new Container();
        $container->bind(DependencyClass::class, fn () => new DependencyClass());

        $resolver  = new Resolver($container);
        $single    = function (DependencyClass $value): void {
        };
        $parameter = (new ReflectionFunction($single))->getParameters()[0];

        $this->assertInstanceOf(
            DependencyClass::class,
            $resolver->resolveParameterDependency($parameter)
        );
    }
}
