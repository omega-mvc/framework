<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\Container\Support\DeepA;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\PrivateConstructorClass;
use Tests\Container\Support\ScalarConstructorClass;
use Tests\Container\Support\Service;
use Tests\Container\Support\UnresolvableClass;
use Tests\TestCase;

#[CoversClass(AliasException::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
final class ResolutionTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testReturnsASharedInstanceThroughGet(): void
    {
        $this->container->bind(DependencyClass::class, null, true);

        $instance1 = $this->container->get(DependencyClass::class);
        $instance2 = $this->container->get(DependencyClass::class);

        $this->assertSame($instance1, $instance2);
    }

    public function testThrowsWhenTheRequestedEntryIsNotFound(): void
    {
        $this->expectException(EntryNotFoundException::class);

        $this->container->get('non-existent-class');
    }

    public function testCreatesAFreshInstanceThroughMake(): void
    {
        $instance1 = $this->container->make(stdClass::class);
        $instance2 = $this->container->make(stdClass::class);

        $this->assertNotSame($instance1, $instance2);
    }

    public function testMakesInstancesWithParameters(): void
    {
        $instance = $this->container->make(Service::class, ['value' => 'custom']);

        $this->assertInstanceOf(Service::class, $instance);
        $this->assertSame('custom', $instance->value);
    }

    public function testResolvesClosuresThroughGet(): void
    {
        $this->container->bind('test-closure', function ($container) {
            return 'resolved from closure';
        });

        $this->assertSame('resolved from closure', $this->container->get('test-closure'));
    }

    public function testResolvesClosuresThroughMake(): void
    {
        $this->container->bind('test-closure', function ($container) {
            return 'resolved from closure';
        });

        $this->assertSame('resolved from closure', $this->container->make('test-closure'));
    }

    public function testResolvesThroughAnAliasWithGet(): void
    {
        $this->container->bind('dependency', DependencyClass::class);
        $this->container->alias('dependency', 'alias');

        $this->assertInstanceOf(DependencyClass::class, $this->container->get('alias'));
    }

    public function testResolvesThroughAnAliasWithMake(): void
    {
        $this->container->bind(DependencyClass::class);
        $this->container->alias(DependencyClass::class, 'dependency_alias');

        $this->assertInstanceOf(DependencyClass::class, $this->container->make('dependency_alias'));
    }

    public function testCachesSingletonInstances(): void
    {
        $counter = 0;

        $this->container->bind(DependencyClass::class, function () use (&$counter) {
            $counter++;

            return new DependencyClass();
        }, true);

        $this->container->get(DependencyClass::class);
        $this->container->get(DependencyClass::class);

        $this->assertSame(1, $counter);
    }

    public function testResolvesRecursiveDependenciesThroughGet(): void
    {
        $instance = $this->container->get(DeepA::class);

        $this->assertInstanceOf(DeepA::class, $instance);
        $this->assertNotSame($instance, $instance->b);
        $this->assertNotSame($instance->b, $instance->b->c);
    }

    public function testResolvesRecursiveDependenciesThroughMake(): void
    {
        $instance = $this->container->make(DeepA::class);

        $this->assertInstanceOf(DeepA::class, $instance);
        $this->assertNotSame($instance, $instance->b);
        $this->assertNotSame($instance->b, $instance->b->c);
    }

    public function testThrowsWhenADependencyCannotBeResolved(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->container->make(UnresolvableClass::class);
    }

    public function testReportsAnUnboundInstantiableClassThroughHas(): void
    {
        $this->assertTrue($this->container->has(stdClass::class));
    }

    public function testReportsAClassWithoutAPublicConstructorAsNotInstantiable(): void
    {
        $this->assertFalse($this->container->has(PrivateConstructorClass::class));
    }

    public function testReportsAResolvedSharedAbstractThroughResolved(): void
    {
        $this->container->bind(DependencyClass::class, null, true);

        $this->assertFalse($this->container->resolved(DependencyClass::class));

        $this->container->get(DependencyClass::class);

        $this->assertTrue($this->container->resolved(DependencyClass::class));
    }

    public function testReturnsTrueFromBoundWhenOnlyACachedInstanceExists(): void
    {
        // get() caches the resolved object in the instances map without ever
        // registering a binding entry, so bound() must pick it up from there.
        $this->container->get(stdClass::class);

        $this->assertTrue($this->container->bound(stdClass::class));
    }

    public function testReturnsAnEmptyArrayAsTheDefaultParameterOverride(): void
    {
        $this->assertSame([], $this->container->getLastParameterOverride());
    }

    public function testAppliesTheActiveParameterOverrideWhenBuildingInsideAFactory(): void
    {
        $this->container->bind('service-factory', function (Container $container) {
            return $container->build(Service::class);
        });

        $instance = $this->container->make('service-factory', ['value' => 'from-override']);

        $this->assertInstanceOf(Service::class, $instance);
        $this->assertSame('from-override', $instance->value);
    }

    public function testResolvesPositionalConstructorParameters(): void
    {
        $instance = $this->container->make(ScalarConstructorClass::class, [0 => 'positional']);

        $this->assertInstanceOf(ScalarConstructorClass::class, $instance);
        $this->assertSame('positional', $instance->getName());
    }
}
