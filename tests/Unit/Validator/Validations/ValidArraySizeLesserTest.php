<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidArraySizeLesserTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test1' => [1, 2, 3],
        'test2' => '',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = ['test' => [1, 2]];

    public function testCanRenderValidArraySizeLesserValidation(): void
    {
        $this->assertEquals('valid_array_size_lesser,1', vr()->valid_array_size_lesser(1));
    }

    public function testCanRenderInvertValidArraySizeLesserValidation(): void
    {
        $this->assertEquals('invert_valid_array_size_lesser,1', vr()->not()->valid_array_size_lesser(1));
    }

    public function testCanValidateValidArraySizeLesserWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test1->valid_array_size_lesser(3);
        $val->test1->valid_array_size_lesser(4);
        $val->test2->valid_array_size_lesser(3);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidArraySizeLesserNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test1->not->valid_array_size_lesser(3);
        $val->test1->not->valid_array_size_lesser(4);
        $val->test2->not->valid_array_size_lesser(3);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidArraySizeLesserWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->valid_array_size_lesser(1);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateRegexNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->not->valid_array_size_lesser(1);

        $this->assertTrue($val->isValid());
    }
}
