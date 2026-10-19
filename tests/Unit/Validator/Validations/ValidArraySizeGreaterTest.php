<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidArraySizeGreaterTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test1' => [1, 2, 3],
        'test2' => '',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = ['test' => [1, 2]];

    public function testCanRenderValidArraySizeGreaterValidation(): void
    {
        $this->assertEquals('valid_array_size_greater,1', vr()->valid_array_size_greater(1));
    }

    public function testCanRenderInvertValidArraySizeGreaterValidation(): void
    {
        $this->assertEquals('invert_valid_array_size_greater,1', vr()->not()->valid_array_size_greater(1));
    }

    public function testCanValidateValidArraySizeGreaterWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test1->valid_array_size_greater(2);
        $val->test1->valid_array_size_greater(3);
        $val->test2->valid_array_size_greater(3);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidArraySizeGreaterNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test1->not->valid_array_size_greater(2);
        $val->test1->not->valid_array_size_greater(3);
        $val->test2->not->valid_array_size_greater(3);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidArraySizeGreaterWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->valid_array_size_greater(3);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateRegexNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->not->valid_array_size_greater(3);

        $this->assertTrue($val->isValid());
    }
}
