<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ValidCcTest extends TestCase
{
    public function testCanRenderValidCcValidation(): void
    {
        $this->assertEquals('valid_cc', vr()->valid_cc());
    }

    public function testCanRenderInvertValidCcValidation(): void
    {
        $this->assertEquals('invert_valid_cc', vr()->not()->valid_cc());
    }

    public function testCanValidateValidCcWithCorrectInput(): void
    {
        $val = new Validator(['card' => '4111 1111 1111 1111']);
        $val->field('card')->valid_cc();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateValidCcWithIncorrectInput(): void
    {
        $val = new Validator(['card' => '4111 1111 1111 1112']);
        $val->field('card')->valid_cc();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateInvertValidCc(): void
    {
        $correct = new Validator(['card' => '4111111111111111']);
        $correct->field('card')->not()->valid_cc();

        $this->assertFalse($correct->isValid());

        $incorrect = new Validator(['card' => '4111111111111112']);
        $incorrect->field('card')->not()->valid_cc();

        $this->assertTrue($incorrect->isValid());
    }
}
