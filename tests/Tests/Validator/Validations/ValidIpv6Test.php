<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidIpv6Test extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test' => '2001:0db8:85a3:08d3:1319:8a2e:0370:7334',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = [
        'tets1' => '2001;0db8;85a3;08d3;1319;8a2e;0370;7334',
        'tets2' => '0,0,0,0',
        'tets3' => '256.0.0.0',
    ];

    public function testCanRenderValidIpv6Validation(): void
    {
        $this->assertEquals('valid_ipv6', vr()->valid_ipv6());
    }

    public function testCanRenderInvertValidIpv6Validation(): void
    {
        $this->assertEquals('invert_valid_ipv6', vr()->not()->valid_ipv6());
    }

    public function testCanValidateValidIpv6WithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->valid_ipv6();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidIpv6NotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->valid_ipv6();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidIpv6WithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->valid_ipv6();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateValidIpv6NotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->valid_ipv6();

        $this->assertTrue($val->isValid());
    }
}
