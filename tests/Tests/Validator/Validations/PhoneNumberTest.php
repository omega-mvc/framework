<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class PhoneNumberTest extends TestCase
{
    /** @var array<string, mixed> */
    private const array CORRECT = [
        'test'  => '555-555-5555',
        'test2' => '5555425555',
        'test3' => '555 555 5555',
        'test4' => '1(222) 555-4444',
        'test5' => '1 (519) 555-4422',
        'test6' => '1-555-555-5555',
        'test7' => '1-(555)-555-5555',
    ];

    /** @var array<string, mixed> */
    private const array INCORRECT = [
        'test8' => '666111222',
        'test9' => '004461234123',
    ];

    public function testCanRenderPhoneNumberValidation(): void
    {
        $this->assertEquals('phone_number', vr()->phone_number());
    }

    public function testCanRenderInvertPhoneNumberValidation(): void
    {
        $this->assertEquals('invert_phone_number', vr()->not()->phone_number());
    }

    public function testCanValidatePhoneNumberWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->phone_number();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateNumericNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->phone_number();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidatePhoneNumberWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->phone_number();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidatePhoneNumberNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->phone_number();

        $this->assertTrue($val->isValid());
    }
}
