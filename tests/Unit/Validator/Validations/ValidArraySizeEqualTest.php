<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidArraySizeEqualTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = ['test' => [1, 2, 3]];

    /** @var array<string, mixed> */
    private const INCORRECT = ['test' => [1, 2]];

    public function testCanRenderValidArraySizeEqualValidation(): void
    {
        $this->assertEquals('valid_array_size_equal,1', vr()->valid_array_size_equal(1));
    }

    public function testCanRenderInvertValidArraySizeEqualValidation(): void
    {
        $this->assertEquals('invert_valid_array_size_equal,1', vr()->not()->valid_array_size_equal(1));
    }

    public function testCanValidateValidArraySizeEqualWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->valid_array_size_equal(3);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidArraySizeEqualNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->not()->valid_array_size_equal(3);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidArraySizeEqualWithIncorrectInputGreateThat(): void
    {
        $val = new Validator(self::INCORRECT);

        // greate that
        $val->test->valid_array_size_equal(3);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidArraySizeEqualWithIncorrectInputLessThat(): void
    {
        $val = new Validator(self::INCORRECT);

        // less that
        $val->test->valid_array_size_equal(1);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateRegexNotWithIncorrectInputGreateThat(): void
    {
        $val = new Validator(self::INCORRECT);

        // greate that
        $val->test->not->valid_array_size_equal(3);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateRegexNotWithIncorrectInputLessThat(): void
    {
        $val = new Validator(self::INCORRECT);

        // less that
        $val->test->not->valid_array_size_equal(1);

        $this->assertTrue($val->isValid());
    }
}
