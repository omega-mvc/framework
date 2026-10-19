<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function array_keys;
use function Omega\Validator\vr;

final class IbanTest extends TestCase
{
    public function testCanRenderIbanValidation(): void
    {
        $this->assertEquals('iban', vr()->iban());
    }

    public function testCanRenderInvertIbanValidation(): void
    {
        $this->assertEquals('invert_iban', vr()->not()->iban());
    }

    public function testCanValidateIbanWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $field_name = array_keys($this->correctInput());
        $val->field(...$field_name)->iban();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateGuidv4NotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $field_name = array_keys($this->correctInput());
        $val->field(...$field_name)->not->iban();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateIbanWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $field_name = array_keys($this->incorrectInput());
        $val->field(...$field_name)->iban();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateIbanNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $field_name = array_keys($this->incorrectInput());
        $val->field(...$field_name)->not->iban();

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return [
            'test1' => 'FR7630006000011234567890189',
            'test2' => 'ES7921000813610123456789',
        ];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return [
            'test1' => 'FR7630006000011234567890181',
            'test2' => 'E7921000813610123456789',
            'test3' => 'text',
        ];
    }
}
