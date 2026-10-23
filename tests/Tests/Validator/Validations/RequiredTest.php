<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class RequiredTest extends TestCase
{
    /** @var array<string, mixed> */
    private const array CORRECT = [
        'test1' => 'test',
        'test2' => '0',
        'test3' => 0.0,
        'test4' => 0,
        'test5' => false,
    ];

    /** @var array<string, mixed> */
    private const array INCORRECT = [
        'test1' => null,
        'test2' => '',
    ];

    public function testCanRenderRequiredValidation(): void
    {
        $this->assertEquals('required', vr()->required());
    }

    public function testCanRenderInvertRequiredValidation(): void
    {
        $this->assertEquals('invert_required', vr()->not()->required());
    }

    public function testCanValidateRegexWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->required();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateRequiredNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->required();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateRequiredWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->required();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateRegexNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->required();

        $this->assertTrue($val->isValid());
    }
}
