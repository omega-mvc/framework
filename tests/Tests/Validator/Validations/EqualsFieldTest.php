<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class EqualsFieldTest extends TestCase
{
    public function testCanRenderEqualsfieldValidation(): void
    {
        $this->assertEquals('equalsfield,other_field_name', vr()->equalsfield('other_field_name'));
    }

    public function testCanRenderInvertEqualsfieldValidation(): void
    {
        $this->assertEquals('invert_equalsfield,other_field_name', vr()->not->equalsfield('other_field_name'));
    }

    public function testCanValidateEqualsfieldWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test->equalsfield('other');

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateEqualsfieldNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test->not->equalsfield('other');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateEqualsfieldWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test->equalsfield('other');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateContainsNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test->not->equalsfield('other');

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return ['test' => 'string', 'other' => 'string'];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return ['test' => 'string', 'other' => 'different_string'];
    }
}
