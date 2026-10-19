<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class AlphaDashTest extends TestCase
{
    public function testCanRenderAlphaDashValidation(): void
    {
        $this->assertEquals('alpha_dash', vr()->alpha_dash());
    }

    public function testCanRenderInvertAlphaDashValidation(): void
    {
        $this->assertEquals('invert_alpha_dash', vr()->not()->alpha_dash());
    }

    public function testCanValidateAlphaWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->alpha_dash();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateAlphaNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->not()->alpha_dash();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->alpha_dash();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->not()->alpha_dash();

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return ['test' => 'my_username-with_dash'];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return ['test' => 'hello123'];
    }
}
