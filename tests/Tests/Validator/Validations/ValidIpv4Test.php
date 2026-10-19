<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidIpv4Test extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test1' => '127.0.0.1',
        'test2' => '1.1.1.1',
        'test3' => '255.255.255.255',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = [
        'tets1' => '2001:0zb8:85a3:08d3:1319:8a2e:0370:7334',
        'tets2' => '0,0,0,0',
        'tets3' => '256.0.0.0',
    ];

    public function testCanRenderValidIpv4Validation(): void
    {
        $this->assertEquals('valid_ipv4', vr()->valid_ipv4());
    }

    public function testCanRenderInvertValidIpv4Validation(): void
    {
        $this->assertEquals('invert_valid_ipv4', vr()->not()->valid_ipv4());
    }

    public function testCanValidateValidIpv4WithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->valid_ipv4();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidIpv4NotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->valid_ipv4();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidIpv4WithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->valid_ipv4();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidIpv4NotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->valid_ipv4();

        $this->assertTrue($val->isValid());
    }
}
