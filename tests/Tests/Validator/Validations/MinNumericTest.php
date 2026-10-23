<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class MinNumericTest extends TestCase
{
    /** @var array<string, mixed> */
    private const array CORRECT = [
        'test1' => 5,
        'test2' => 10,
        'test3' => '',
    ];

    /** @var array<string, mixed> */
    private const array INCORRECT = [
        'test' => 3,
    ];

    public function testCanRenderMinNumericValidation(): void
    {
        $this->assertEquals('min_numeric,5', vr()->min_numeric(5));
    }

    public function testCanRenderInvertMinNumericValidation(): void
    {
        $this->assertEquals('invert_min_numeric,5', vr()->not->min_numeric(5));
    }

    public function testCanValidateMinNumericWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->field('test1', 'test2', 'test3')->min_numeric(5);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateMinNumericNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->field('test1', 'test2', 'test3')->not->min_numeric(5);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMinNumericWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        // less
        $val->field('test')->min_numeric(5);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMinNumericNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        // less
        $val->field('test')->not->min_numeric(5);

        $this->assertTrue($val->isValid());
    }
}
