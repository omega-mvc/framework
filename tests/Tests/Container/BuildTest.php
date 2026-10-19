<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Resolver;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\Container\Support\CircularA;
use Tests\Container\Support\ClassWithMissingDependency;
use Tests\Container\Support\ClassWithNullableUnionTypeConstructor;
use Tests\Container\Support\ClassWithUnionTypeConstructor;
use Tests\Container\Support\Dependant;
use Tests\Container\Support\PrivateConstructorClass;
use Tests\Container\Support\ScalarConstructorClass;
use Tests\Container\Support\Service;
use Tests\Container\Support\TypedConstructorClass;
use Tests\Container\Support\UnionDependencyOne;
use Tests\Container\Support\UnionDependencyTwo;
use Tests\TestCase;

#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Resolver::class)]
final class BuildTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBuildsAClassWithoutDependencies(): void
    {
        $this->assertInstanceOf(stdClass::class, $this->container->build(stdClass::class));
    }

    public function testBuildsAClassWithDependencies(): void
    {
        $instance = $this->container->build(Dependant::class);

        $this->assertInstanceOf(Dependant::class, $instance);
        $this->assertNotSame($instance, $instance->dep);
    }

    public function testBuildsAClassWithCustomParameters(): void
    {
        $instance = $this->container->build(Service::class, ['value' => 'custom']);

        $this->assertInstanceOf(Service::class, $instance);
        $this->assertSame('custom', $instance->value);
    }

    public function testBuildsFromAClosure(): void
    {
        $this->assertSame('foo', $this->container->build(fn () => 'foo'));
    }

    public function testThrowsWhenADependencyIsMissing(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->container->build(ClassWithMissingDependency::class);
    }

    public function testThrowsOnCircularDependencies(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->container->build(CircularA::class);
    }

    public function testBuildsClassesWithTypedConstructors(): void
    {
        $instance = $this->container->build(TypedConstructorClass::class);

        $this->assertInstanceOf(TypedConstructorClass::class, $instance);
        $this->assertNotSame($instance, $instance->dep);
    }

    public function testResolvesTheFirstBoundUnionType(): void
    {
        $this->container->bind(UnionDependencyOne::class, fn () => new UnionDependencyOne());
        $instance = $this->container->build(ClassWithUnionTypeConstructor::class);

        $this->assertInstanceOf(ClassWithUnionTypeConstructor::class, $instance);
        $this->assertInstanceOf(UnionDependencyOne::class, $instance->dependency);
    }

    public function testResolvesTheSecondBoundUnionType(): void
    {
        $this->container->bind(UnionDependencyTwo::class, fn () => new UnionDependencyTwo());
        $instance = $this->container->build(ClassWithUnionTypeConstructor::class);

        $this->assertInstanceOf(ClassWithUnionTypeConstructor::class, $instance);
        $this->assertInstanceOf(UnionDependencyTwo::class, $instance->dependency);
    }

    public function testThrowsWhenNoUnionTypeIsBound(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->container->build(ClassWithUnionTypeConstructor::class);
    }

    public function testResolvesNullableUnionTypeConstructorsToNull(): void
    {
        $instance = $this->container->build(ClassWithNullableUnionTypeConstructor::class);

        $this->assertInstanceOf(ClassWithNullableUnionTypeConstructor::class, $instance);
        $this->assertNull($instance->dependency);
    }

    public function testThrowsForScalarConstructorParameters(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->container->build(ScalarConstructorClass::class);
    }

    public function testThrowsForPrivateConstructors(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->container->build(PrivateConstructorClass::class);
    }
}
