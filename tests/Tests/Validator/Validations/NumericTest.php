<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class NumericTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test1' => 123,
        'test2' => 1.2,
        'test3' => 0,
        'test4' => '0',
        'test5' => -1,
        'test6' => '123',
        'test7' => '-1',
        'test8' => '',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = [
        'test' => 'n0t',
    ];

    public function testCanRenderNumericValidation(): void
    {
        $this->assertEquals('numeric', vr()->numeric());
    }

    public function testCanRenderInvertNumericValidation(): void
    {
        $this->assertEquals('invert_numeric', vr()->not()->numeric());
    }

    public function testCanValidateNumericWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->numeric();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateNumericNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->numeric();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateNumericWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->numeric();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateNumericNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->not->numeric();

        $this->assertTrue($val->isValid());
    }
}
