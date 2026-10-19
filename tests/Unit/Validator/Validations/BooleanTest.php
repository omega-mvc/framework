<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function array_keys;
use function Omega\Validator\vr;

final class BooleanTest extends TestCase
{
    public function testCanRenderBooleanValidation(): void
    {
        $this->assertEquals('boolean', vr()->boolean(false));
    }

    public function testCanRenderBooleanValidationStrict(): void
    {
        $this->assertEquals('boolean,strict', vr()->boolean(true));
    }

    public function testCanRenderInvertBooleanValidation(): void
    {
        $this->assertEquals('invert_boolean', vr()->not->boolean(false));
    }

    public function testCanRenderInvertBooleanValidationStrict(): void
    {
        $this->assertEquals('invert_boolean,strict', vr()->not->boolean(true));
    }

    public function testCanValidateBooleanWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $field_name = array_keys($this->correctInput());
        $val->field(...$field_name)->boolean(false);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateBooleanWithCorrectInputStrict(): void
    {
        $val = new Validator($this->correctStrictInput());

        $val->test1->boolean(true);
        $val->test2->boolean(true);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateBooleanNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $field_name = array_keys($this->correctInput());
        $val->field(...$field_name)->not->boolean(false);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateBooleanNotWithCorrectInputStrict(): void
    {
        $val = new Validator($this->correctStrictInput());

        $val->test1->not->boolean(true);
        $val->test2->not->boolean(true);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateBooleanWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $field_name = array_keys($this->incorrectInput());
        $val->field(...$field_name)->boolean(false);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateBooleanNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $field_name = array_keys($this->incorrectInput());
        $val->field(...$field_name)->not->boolean(false);

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return [
            'test1' => 'true',
            'test2' => 'false',
            'test3' => 'on',
            'test4' => 'off',
            'test5' => '1',
            'test6' => '0',
            'test7' => 'yes',
            'test8' => 'no',
        ];
    }

    /** @return array<string, bool> */
    private function correctStrictInput(): array
    {
        return [
            'test1' => true,
            'test2' => false,
        ];
    }

    /** @return array<string, int|string> */
    private function incorrectInput(): array
    {
        return [
            'test1' => 'randomString',
            'test2' => 111,
            'test3' => 'TRUE',
            'test4' => 'False',
        ];
    }
}
