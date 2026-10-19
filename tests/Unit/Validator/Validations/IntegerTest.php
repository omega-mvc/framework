<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function array_keys;
use function Omega\Validator\vr;

final class IntegerTest extends TestCase
{
    public function testCanRenderIntegerValidation(): void
    {
        $this->assertEquals('integer', vr()->integer());
    }

    public function testCanRenderInvertIntegerValidation(): void
    {
        $this->assertEquals('invert_integer', vr()->not()->integer());
    }

    public function testCanValidateIntegerWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $field_name = array_keys($this->correctInput());
        $val->field(...$field_name)->integer();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateIntegerNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $field_name = array_keys($this->correctInput());
        $val->field(...$field_name)->not->integer();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateIntegerWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $field_name = array_keys($this->incorrectInput());
        $val->field(...$field_name)->integer();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateIbanNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $field_name = array_keys($this->incorrectInput());
        $val->field(...$field_name)->not->integer();

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, int|string> */
    private function correctInput(): array
    {
        return [
            'test1' => '123',
            'test2' => 123,
            'test3' => -1,
            'test4' => 0,
            'test5' => '0',
        ];
    }

    /** @return array<string, array<int, string>|bool|float|int|string|null> */
    private function incorrectInput(): array
    {
        return [
            'test1' => 'text',
            'test2' => true,
            'test3' => null,
            'test4' => 1.1,
            'test5' => '1.1',
            'test6' => ['array'],
        ];
    }
}
