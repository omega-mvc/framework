<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Container\Support\DummyClass;
use Tests\TestCase;

#[CoversClass(AliasException::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
final class AliasTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testResolvesABasicAlias(): void
    {
        $this->container->bind('foo', fn () => 'foo');
        $this->container->alias('foo', 'foo-alias');

        $this->assertSame('foo', $this->container->get('foo-alias'));
    }

    public function testResolvesAliasChainsRecursively(): void
    {
        $this->container->bind('foo', fn () => 'bar');
        $this->container->alias('foo', 'alias1');
        $this->container->alias('alias1', 'alias2');

        $this->assertSame('bar', $this->container->get('alias2'));
    }

    public function testLetsTheLatestAliasDefinitionShadowPreviousOnes(): void
    {
        $this->container->bind('foo', fn () => 'foo-instance');
        $this->container->bind('bar', fn () => 'bar-instance');
        $this->container->alias('foo', 'shadow');
        $this->container->alias('bar', 'shadow');

        $this->assertSame('bar-instance', $this->container->get('shadow'));
    }

    public function testReturnsTheResolvedAbstractForAnAlias(): void
    {
        $this->container->alias('foo', 'bar');

        $this->assertSame('foo', $this->container->getAlias('bar'));
    }

    public function testResolvesABindingThroughAnAliasUsedAsAbstract(): void
    {
        $this->container->alias('foo', 'bar');
        $this->container->bind('bar', fn () => 'baz');

        $this->assertSame('baz', $this->container->get('foo'));
    }

    public function testThrowsWhenAnAliasMapsToItself(): void
    {
        $this->expectException(AliasException::class);

        $this->container->alias('foo', 'foo');
    }

    public function testThrowsWhenAnAliasChainIsCircular(): void
    {
        $this->container->alias('foo', 'bar');
        $this->container->alias('bar', 'foo');

        $this->expectException(CircularAliasException::class);

        $this->container->get('foo');
    }

    public function testSharesTheResolvedInstanceBetweenABindingAndItsAlias(): void
    {
        $this->container->bind(DummyClass::class, null, true);
        $this->container->alias(DummyClass::class, 'dummy_alias');

        $instance1 = $this->container->get(DummyClass::class);
        $instance2 = $this->container->get('dummy_alias');

        $this->assertSame($instance1, $instance2);
    }
}
