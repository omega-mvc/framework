<?php

declare(strict_types=1);

namespace Tests\Validator\Validations\BackwardsCompatible;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class Equals_FieldTest extends TestCase
{
    /** @var array<string, mixed> */
    private const array CORRECT = ['test' => 'string', 'other' => 'string'];

    /** @var array<string, mixed> */
    private const array INCORRECT = ['test' => 'string', 'other' => 'different_string'];

    public function testCanRenderEqualsFieldValidation(): void
    {
        $this->assertEquals('equalsfield,other_field_name', vr()->equals_field('other_field_name'));
    }

    public function testCanRenderInvertEqualsFieldValidation(): void
    {
        $this->assertEquals('invert_equalsfield,other_field_name', vr()->not->equals_field('other_field_name'));
    }

    public function testCanValidateEqualsFieldWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->equals_field('other');

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateEqualsFieldNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->not->equals_field('other');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateEqualsFieldWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->equals_field('other');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateContainsNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->not->equals_field('other');

        $this->assertTrue($val->isValid());
    }
}
