<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\Container\Support\DummyClass;
use Tests\TestCase;

#[CoversClass(AliasException::class)]
#[CoversClass(Container::class)]
final class ArrayAccessTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testSetsAnEntryThroughArrayAccess(): void
    {
        $this->container['foo'] = 'bar';

        $this->assertTrue(isset($this->container['foo']));
    }

    public function testGetsAnEntryThroughArrayAccess(): void
    {
        $this->container['foo'] = 'bar';

        $this->assertSame('bar', $this->container['foo']);
    }

    public function testReportsWhetherAnEntryExists(): void
    {
        $this->container['foo'] = 'bar';

        $this->assertTrue(isset($this->container['foo']));
        $this->assertFalse(isset($this->container['baz']));
    }

    public function testRemovesAnEntryThroughArrayAccess(): void
    {
        $this->container['foo'] = 'bar';
        unset($this->container['foo']);

        $this->assertFalse(isset($this->container['foo']));
    }

    public function testCreatesANewInstanceOnEachArrayRead(): void
    {
        $this->container['foo'] = fn () => new stdClass();

        $instance1 = $this->container['foo'];
        $instance2 = $this->container['foo'];

        $this->assertNotSame($instance1, $instance2);
    }

    public function testResolvesTheContainerInstanceThroughArrayAccess(): void
    {
        $this->container['std'] = fn () => new stdClass();

        $this->assertInstanceOf(stdClass::class, $this->container->offsetGet('std'));
    }

    public function testRespectsAliasesThroughArrayAccess(): void
    {
        $this->container->alias(DummyClass::class, 'dummy_alias');
        $this->container['dummy_alias'] = fn () => new DummyClass();

        $this->assertInstanceOf(DummyClass::class, $this->container->offsetGet('dummy_alias'));
    }

    public function testRemovesAliasesPointingToAnUnsetEntry(): void
    {
        $this->container->bind('foo', fn () => 'foo-value');
        $this->container->alias('foo', 'alias1');
        $this->container->alias('foo', 'alias2');

        unset($this->container['foo']);

        $this->assertFalse(isset($this->container['foo']));
        $this->assertFalse(isset($this->container['alias1']));
        $this->assertFalse(isset($this->container['alias2']));
    }

    public function testKeepsUnrelatedAliasesWhenUnsettingThroughAnAlias(): void
    {
        $this->container->bind('bar', fn () => new stdClass());
        $this->container->alias('bar', 'b1');
        $this->container->bind('qux', fn () => new stdClass());
        $this->container->alias('qux', 'q1');

        unset($this->container['b1']);

        $this->assertFalse(isset($this->container['bar']));
        $this->assertFalse(isset($this->container['b1']));
        $this->assertTrue(isset($this->container['qux']));
        $this->assertTrue(isset($this->container['q1']));
    }
}
