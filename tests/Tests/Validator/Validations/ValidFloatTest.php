<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidFloatTest extends TestCase
{
    /** @var array<string, mixed> */
    private const array CORRECT = [
        'test1' => 0,
        'test2' => 1.1,
        'test3' => '1.1',
        'test4' => -1.1,
        'test5' => '-1.1',
    ];

    /** @var array<string, mixed> */
    private const array INCORRECT = [
        'test1' => '1,1',
        'test2' => '1.0,0',
        'test3' => '1,0.0',
        'test4' => 'text',
    ];

    public function testCanRenderFloatValidation(): void
    {
        $this->assertEquals('float', vr()->float());
    }

    public function testCanRenderInvertFloatValidation(): void
    {
        $this->assertEquals('invert_float', vr()->not()->float());
    }

    public function testCanValidateFloatWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->float();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateFloatNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->float();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateFloatWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->float();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateIbanNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->float();

        $this->assertTrue($val->isValid());
    }
}
