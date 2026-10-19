<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidEmailTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test1' => 'test@gmail.com',
        'test2' => 'test@yahoo.com',
        'test3' => 'test@hotmail.com',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = [
        'test1' => 'test',
        'test2' => 'test@gmail',
    ];

    public function testCanRenderValidEmailValidation(): void
    {
        $this->assertEquals('valid_email', vr()->valid_email());
    }

    public function testCanRenderInvertValidEmailValidation(): void
    {
        $this->assertEquals('invert_valid_email', vr()->not->valid_email());
    }

    public function testCanValidateValidEmailWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->valid_email();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidEmailNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->valid_email();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidEmailWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->valid_email();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidEmailNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->valid_email();

        $this->assertTrue($val->isValid());
    }
}
