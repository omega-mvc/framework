<?php

/**
 * Part of Omega - Tests\Support Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support\Enum;

use Omega\Support\Enum\AbstractEnum;
use Omega\Support\Enum\Exceptions\BadInstantiationException;
use Omega\Support\Enum\Exceptions\InvalidValueException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Enum\Support\TestEnum;

/**
 * Tests the behavior of AbstractEnum implementations.
 *
 * Ensures correct instantiation, validation, value retrieval,
 * constant enumeration, and string casting behavior.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Enum
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(AbstractEnum::class)]
#[CoversClass(BadInstantiationException::class)]
#[CoversClass(InvalidValueException::class)]
class EnumTest extends TestCase
{
    /**
     * Test should create from valid values.
     *
     * @return void
     */
    public function testShouldCreateFromValidValues(): void
    {
        $this->assertInstanceOf(TestEnum::class, TestEnum::from(TestEnum::CONST_1));
        $this->assertInstanceOf(TestEnum::class, TestEnum::from(TestEnum::CONST_2));
        $this->assertInstanceOf(TestEnum::class, TestEnum::from(TestEnum::CONST_3));
    }

    /**
     * Test should throw if invalid value.
     *
     * @retrn void
     */
    public function testShouldThrowIfInvalidValue(): void
    {
        $this->expectException(InvalidValueException::class);

        TestEnum::from(9);
    }

    /**
     * Test should throw if creating abstract enum.
     *
     * @return void
     */
    public function testShouldThrowIfCreatingAbstractEnum(): void
    {
        $this->expectException(BadInstantiationException::class);

        AbstractEnum::from(9);
    }

    /**
     * Test should return constant value.
     *
     * @return void
     */
    public function testShouldReturnConstantValue(): void
    {
        $this->assertEquals(0, TestEnum::from(TestEnum::CONST_1)->value());
        $this->assertEquals('Const 3', TestEnum::from(TestEnum::CONST_3)->value());
    }

    /**
     * Test should return all constants.
     *
     * @return void
     */
    public function testShouldReturnAllConstants(): void
    {
        $this->assertEquals(array('CONST_1', 'CONST_2', 'CONST_3'), TestEnum::enum());
    }

    /**
     * Test should return all values.
     *
     * @return void
     */
    public function testShouldReturnAllValues(): void
    {
        $this->assertEquals(array(0, 1, 'Const 3'), TestEnum::values());
    }

    /**
     * Test should return constant name when cast to string.
     *
     * @return void
     */
    public function testShouldReturnConstantNameWhenCastToString(): void
    {
        $this->assertEquals('CONST_2', (string)TestEnum::from(TestEnum::CONST_2));
    }

    /**
     * Test should return string value if resent when cast to string.
     *
     * @return void
     */
    public function testShouldReturnStringValueIfPresentWhenCastToString(): void
    {
        $this->assertEquals('Const 1', (string)TestEnum::from(TestEnum::CONST_1));
    }
}
