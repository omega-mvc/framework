<?php

declare(strict_types=1);

namespace Tests\Container;

use Closure;
use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\Container\Support\AnotherService;
use Tests\Container\Support\ConcreteService;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\ServiceInterface;
use Tests\TestCase;

#[CoversClass(AliasException::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
final class BindingTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBindsAnAbstractionToAConcreteClass(): void
    {
        $this->container->bind(ServiceInterface::class, ConcreteService::class);

        $this->assertInstanceOf(ConcreteService::class, $this->container->get(ServiceInterface::class));
    }

    public function testBindsAClosure(): void
    {
        $this->container->bind('foo', fn () => 'bar');

        $this->assertSame('bar', $this->container->get('foo'));
    }

    public function testBindsASharedSingleton(): void
    {
        $this->container->bind('foo', fn () => new stdClass(), true);

        $instance1 = $this->container->get('foo');
        $instance2 = $this->container->get('foo');

        $this->assertSame($instance1, $instance2);
    }

    public function testCreatesANewInstanceForNonSharedBindings(): void
    {
        $this->container->bind('foo', fn () => new stdClass());

        $instance1 = $this->container->make('foo');
        $instance2 = $this->container->make('foo');

        $this->assertNotSame($instance1, $instance2);
    }

    public function testOverridesAPreviousBinding(): void
    {
        $this->container->bind('foo', fn () => 'bar');
        $this->container->bind('foo', fn () => 'baz');

        $this->assertSame('baz', $this->container->get('foo'));
    }

    public function testBindsAClassNameToItself(): void
    {
        $this->container->bind(stdClass::class, stdClass::class);

        $this->assertInstanceOf(stdClass::class, $this->container->get(stdClass::class));
    }

    public function testUsesTheAbstractAsConcreteWhenConcreteIsNull(): void
    {
        $this->container->bind(stdClass::class);

        $this->assertInstanceOf(stdClass::class, $this->container->get(stdClass::class));
    }

    public function testBindsMultipleUnrelatedIdentifiers(): void
    {
        $this->container->bind('foo', stdClass::class);
        $this->container->bind('bar', AnotherService::class);

        $this->assertInstanceOf(stdClass::class, $this->container->get('foo'));
        $this->assertInstanceOf(AnotherService::class, $this->container->get('bar'));
    }

    public function testReturnsScalarValuesFromBoundClosures(): void
    {
        $this->container->bind('string_value', fn () => 'hello');
        $this->container->bind('int_value', fn () => 123);

        $this->assertSame('hello', $this->container->get('string_value'));
        $this->assertSame(123, $this->container->get('int_value'));
    }

    public function testInjectsDependenciesIntoBoundClosures(): void
    {
        $this->container->bind('with_param', function (DependencyClass $dep) {
            return $dep;
        });

        $this->assertInstanceOf(DependencyClass::class, $this->container->get('with_param'));
    }

    public function testRespectsAliasResolutionOnBindings(): void
    {
        $this->container->alias(ServiceInterface::class, 'my_interface_alias');
        $this->container->bind('my_interface_alias', AnotherService::class);

        $instance = $this->container->get(ServiceInterface::class);

        $this->assertInstanceOf(AnotherService::class, $instance);
    }

    public function testReportsAnExistingBindingThroughHas(): void
    {
        $this->container->bind('foo', stdClass::class);

        $this->assertTrue($this->container->has('foo'));
    }

    public function testReportsAMissingBindingThroughHas(): void
    {
        $this->assertFalse($this->container->has('non-existent-binding'));
    }

    public function testMirrorsHasBehaviorThroughBound(): void
    {
        $this->container->bind('foo', stdClass::class);

        $this->assertTrue($this->container->bound('foo'));
        $this->assertFalse($this->container->bound('non-existent'));
    }

    public function testRespectsAliasResolutionThroughBound(): void
    {
        $this->container->bind(ServiceInterface::class, ConcreteService::class);
        $this->container->alias(ServiceInterface::class, 'my_service_alias');

        $this->assertTrue($this->container->bound('my_service_alias'));
        $this->assertTrue($this->container->has('my_service_alias'));
    }

    public function testReturnsAllCurrentBindingsWithTheirMetadata(): void
    {
        $this->container->bind('foo', stdClass::class);
        $this->container->bind('bar', ConcreteService::class, true);

        $bindings = $this->container->getBindings();

        $this->assertArrayHasKey('foo', $bindings);
        $this->assertArrayHasKey('bar', $bindings);
        $this->assertFalse($bindings['foo']['shared']);
        $this->assertTrue($bindings['bar']['shared']);
    }

    public function testUpdatesBindingsAfterAnOverride(): void
    {
        $this->container->bind('foo', stdClass::class);
        $this->container->bind('foo', ConcreteService::class);

        $bindings = $this->container->getBindings();

        $this->assertArrayHasKey('foo', $bindings);
        $this->assertInstanceOf(ConcreteService::class, $this->container->get('foo'));
    }

    public function testEmptiesBindingsAfterFlush(): void
    {
        $this->container->bind('foo', stdClass::class);
        $this->container->flush();

        $this->assertEmpty($this->container->getBindings());
    }

    public function testStoresAClosureFactoryAsASharedBindingThroughSet(): void
    {
        $this->container->set('shared-factory', fn () => new stdClass());

        $this->assertSame(
            $this->container->get('shared-factory'),
            $this->container->get('shared-factory')
        );

        $bindings = $this->container->getBindings();

        $this->assertTrue($bindings['shared-factory']['shared']);
    }

    public function testStoresPlainValuesAsResolvedSingletonsThroughSet(): void
    {
        $this->container->set('plain.value', 'plain');

        $this->assertSame('plain', $this->container->get('plain.value'));
    }

    public function testResolvesSetValuesAgainstTheCanonicalAbstractThroughAnAlias(): void
    {
        $this->container->alias('canonical', 'the-alias');
        $this->container->set('the-alias', 'via-alias');

        $this->assertSame('via-alias', $this->container->get('canonical'));
    }
}
