<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use stdClass;
use Tests\TestCase;

#[CoversClass(AliasException::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
final class FlushTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testRemovesBindingsOnFlush(): void
    {
        $this->container->bind('foo', fn () => 'bar');

        $this->assertTrue($this->container->bound('foo'));

        $this->container->flush();

        $this->assertFalse($this->container->bound('foo'));
    }

    public function testClearsResolvedInstancesOnFlush(): void
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

    public function testClearsAliasesOnFlush(): void
    {
        $this->container->bind(stdClass::class);
        $this->container->alias(stdClass::class, 'foo');

        $this->assertInstanceOf(stdClass::class, $this->container->get('foo'));

        $this->container->flush();

        $this->expectException(EntryNotFoundException::class);

        $this->container->get('foo');
    }

    public function testResetsAllInternalStateOnFlush(): void
    {
        $this->container->bind('foo', fn () => new stdClass(), true);
        $this->container->get('foo');
        $this->container->alias('foo', 'bar');

        $this->container->flush();

        $value = function (string $property): mixed {
            $reflection = new ReflectionProperty($this->container, $property);

            return $reflection->getValue($this->container);
        };

        $this->assertEmpty($value('bindings'));
        $this->assertEmpty($value('instances'));
        $this->assertEmpty($value('aliases'));
        $this->assertEmpty($value('reflectionCache'));
    }

    public function testResetsTheReflectionCacheOnClearCache(): void
    {
        $ref1 = $this->container->getReflectionClass(stdClass::class);
        $ref2 = $this->container->getReflectionClass(stdClass::class);

        $this->assertSame($ref1, $ref2);

        $this->container->clearCache();

        $ref3 = $this->container->getReflectionClass(stdClass::class);

        $this->assertNotSame($ref1, $ref3);
    }

    public function testReturnsTheContainerFromClearCache(): void
    {
        $this->assertSame($this->container, $this->container->clearCache());
    }

    public function testReturnsTheContainerFromSetRequestScoped(): void
    {
        $this->assertSame($this->container, $this->container->setRequestScoped('foo'));
    }

    public function testDiscardsRequestScopedInstancesOnReset(): void
    {
        $this->container->bind('foo', fn () => new stdClass(), true);
        $this->container->setRequestScoped('foo');

        $instance1 = $this->container->get('foo');
        $instance2 = $this->container->get('foo');

        $this->assertSame($instance1, $instance2);

        $this->container->resetRequestScope();

        $instance3 = $this->container->get('foo');

        $this->assertNotSame($instance1, $instance3);
        $this->assertTrue($this->container->bound('foo'));
    }

    public function testKeepsUnresolvableRequestScopedBindingsAfterReset(): void
    {
        $this->container->setRequestScoped('never-resolved');

        $this->container->resetRequestScope();

        $this->assertFalse($this->container->bound('never-resolved'));
    }

    public function testResetsRequestScopedBindingsRegisteredThroughAnAlias(): void
    {
        $this->container->bind('foo', fn () => new stdClass(), true);
        $this->container->alias('foo', 'foo-alias');
        $this->container->setRequestScoped('foo-alias');

        $instance1 = $this->container->get('foo');

        $this->container->resetRequestScope();

        $instance2 = $this->container->get('foo');

        $this->assertNotSame($instance1, $instance2);
    }

    public function testResetsAnEmptyRequestScopeWithoutTouchingInstances(): void
    {
        $this->container->bind('foo', fn () => new stdClass(), true);

        $instance1 = $this->container->get('foo');

        // No binding is marked request-scoped, so the reset loop body never runs
        // and the cached instance survives untouched.
        $this->container->resetRequestScope();
        $this->container->resetRequestScope();

        $instance2 = $this->container->get('foo');

        $this->assertSame($instance1, $instance2);
    }

    public function testResetsMultipleRequestScopedBindingsInASinglePass(): void
    {
        $this->container->bind('one', fn () => new stdClass(), true);
        $this->container->bind('two', fn () => new stdClass(), true);
        $this->container->setRequestScoped('one');
        $this->container->setRequestScoped('two');

        $instance1 = $this->container->get('one');
        $instance2 = $this->container->get('two');

        $this->container->resetRequestScope();

        $instance3 = $this->container->get('one');
        $instance4 = $this->container->get('two');

        $this->assertNotSame($instance1, $instance3);
        $this->assertNotSame($instance2, $instance4);
    }
}
