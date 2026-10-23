<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class MinAgeTest extends TestCase
{
    /** @var array<string, mixed> */
    private const array CORRECT = ['test' => '1997-06-16'];

    /** @var array<string, mixed> */
    private const array INCORRECT = ['test' => '2022-01-11'];

    public function testCanRenderMinAgeValidation(): void
    {
        $this->assertEquals('min_age,18', vr()->min_age(18));
    }

    public function testCanRenderInvertMinAgeValidation(): void
    {
        $this->assertEquals('invert_min_age,18', vr()->not()->min_age(18));
    }

    public function testCanValidateMinAgeWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->min_age(23);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateMinAgeNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->not()->min_age(23);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMinAgeWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->min_age(23);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMinAgeNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->not()->min_age(23);

        $this->assertTrue($val->isValid());
    }
}
