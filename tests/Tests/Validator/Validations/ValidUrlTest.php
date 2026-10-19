<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidUrlTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test1' => 'http://test.com/',
        'test2' => 'http://test.com',
        'test3' => 'https://test.com',
        'test4' => 'tcp://test.com',
        'test5' => 'ftp://test.com',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = [
        'test1' => 'example.com',
        'test2' => 'text',
    ];

    public function testCanRenderValidUrlValidation(): void
    {
        $this->assertEquals('valid_url', vr()->valid_url());
    }

    public function testCanRenderInvertValidUrlValidation(): void
    {
        $this->assertEquals('invert_valid_url', vr()->not->valid_url());
    }

    public function testCanValidateValidUrlWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->valid_url();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidUrlNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->valid_url();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidUrlWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->valid_url();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateIbanNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->valid_url();

        $this->assertTrue($val->isValid());
    }
}
