<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidNameTest extends TestCase
{
    /** @var array<string, mixed> */
    private const array CORRECT = [
        'test1' => 'taylorotwell',
        'tets2' => '',
    ];

    /** @var array<string, mixed> */
    private const array INCORRECT = [
        'tets1' => 'sa`ad',
        'tets2' => 's@ad',
        'test3' => 'Mr. Sigurd Heller MD',
    ];

    public function testCanRenderValidNameValidation(): void
    {
        $this->assertEquals('valid_name', vr()->valid_name());
    }

    public function testCanRenderInvertValidNameValidation(): void
    {
        $this->assertEquals('invert_valid_name', vr()->not()->valid_name());
    }

    public function testCanValidateValidNameWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->valid_name();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidNameNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->valid_name();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidNameWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->valid_name();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidNameNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->valid_name();

        $this->assertTrue($val->isValid());
    }
}
