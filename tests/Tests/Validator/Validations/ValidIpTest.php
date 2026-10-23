<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidIpTest extends TestCase
{
    /** @var array<string, mixed> */
    private const array CORRECT = [
        'test1' => '2001:0db8:85a3:08d3:1319:8a2e:0370:7334',
        'test2' => '127.0.0.1',
        'test3' => '255.255.255.255',
    ];

    /** @var array<string, mixed> */
    private const array INCORRECT = [
        'tets1' => '2001:0zb8:85a3:08d3:1319:8a2e:0370:7334',
        'tets2' => '0,0,0,0',
        'tets3' => '256.0.0.0',
    ];

    public function testCanRenderValidIpValidation(): void
    {
        $this->assertEquals('valid_ip', vr()->valid_ip());
    }

    public function testCanRenderInvertValidIpValidation(): void
    {
        $this->assertEquals('invert_valid_ip', vr()->not()->valid_ip());
    }

    public function testCanValidateValidIpWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->valid_ip();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidIpNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->valid_ip();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidIpWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->valid_ip();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidIpNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->valid_ip();

        $this->assertTrue($val->isValid());
    }
}
