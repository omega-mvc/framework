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

/** @noinspection PhpConditionAlreadyCheckedInspection */

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Exceptions\AliasException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\Container\Fixtures\DummyClass;

/**
 * Tests the ArrayAccess implementation of the container.
 *
 * This test class verifies that the container behaves correctly when accessed
 * like an array, including setting, getting, checking existence, unsetting
 * entries, resolving closures, handling shared bindings, and respecting aliases.
 *
 * @category  Tests
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(AliasException::class)]
class ArrayAccessTest extends AbstractTestContainer
{
    /**
     * Test array set.
     *
     * @return void
     */
    public function testArraySet(): void
    {
        $container        = $this->container;
        $container['foo'] = 'bar';
        $this->assertTrue(isset($container['foo']));
    }

    /**
     * Test array get.
     *
     * @return void
     */
    public function testArrayGet(): void
    {
        $container        = $this->container;
        $container['foo'] = 'bar';
        $this->assertEquals('bar', $container['foo']);
    }

    /**
     * Test array exists.
     *
     * @return void
     */
    public function testArrayExists(): void
    {
        $container        = $this->container;
        $container['foo'] = 'bar';
        $this->assertTrue(isset($container['foo']));
        $this->assertFalse(isset($container['baz']));
    }

    /**
     * est array unset.
     *
     * @return void
     */
    public function testArrayUnset(): void
    {
        $container        = $this->container;
        $container['foo'] = 'bar';
        $this->assertTrue(isset($container['foo']));
        unset($container['foo']);
        $this->assertFalse(isset($container['foo']));
    }

    /**
     * Test array get returns new instance.
     *
     * @return void
     */
    public function testArrayGetReturnsNewInstance(): void
    {
        $container = $this->container;
        $container['foo'] = fn () => new stdClass();
        $instance1 = $container['foo'];
        $instance2 = $container['foo'];
        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test array get resolves container.
     *
     * @return void
     */
    public function testArrayGetResolvesContainer(): void
    {
        $container = $this->container;
        $container['std'] = fn () => new stdClass(); // Bind a closure that returns an instance
        $instance = $container['std'];
        $this->assertInstanceOf(stdClass::class, $instance);
    }

    /**
     * Test offset set stores shared binding.
     *
     * @return void
     */
    public function testOffsetSetStoresSharedBinding(): void
    {
        $container = $this->container;
        $container['foo'] = fn () => new stdClass();
        $instance1 = $container['foo'];
        $instance2 = $container['foo'];
        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test array access respects alias.
     *
     * @return void
     * @throws AliasException Thrown when an alias maps to itself.
     */
    public function testArrayAccessRespectsAlias(): void
    {
        $this->container->alias(DummyClass::class, 'dummy_alias');
        $this->container['dummy_alias'] = fn () => new DummyClass();
        $instance = $this->container['dummy_alias'];
        $this->assertInstanceOf(DummyClass::class, $instance);
    }
}
