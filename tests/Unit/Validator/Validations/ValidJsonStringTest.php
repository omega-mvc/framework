<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidJsonStringTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test1' => '{}',
        'test2' => '{"testing": true}',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = [
        'tets1' => '{}}',
        'tets2' => '{test:true}',
        'tets3' => '{"test":text}',
    ];

    public function testCanRenderValidJsonStringValidation(): void
    {
        $this->assertEquals('valid_json_string', vr()->valid_json_string());
    }

    public function testCanRenderInvertValidJsonStringValidation(): void
    {
        $this->assertEquals('invert_valid_json_string', vr()->not()->valid_json_string());
    }

    public function testCanValidateValidJsonStringWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->valid_json_string();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidJsonStringNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->valid_json_string();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidJsonStringWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->valid_json_string();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidJsonStringNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->valid_json_string();

        $this->assertTrue($val->isValid());
    }
}
