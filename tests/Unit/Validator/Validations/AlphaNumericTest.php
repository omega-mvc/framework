<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class AlphaNumericTest extends TestCase
{
    public function testCanRenderAlphaNumericValidation(): void
    {
        $this->assertEquals('alpha_numeric', vr()->alpha_numeric());
    }

    public function testCanRenderInvertAlphaNumericValidation(): void
    {
        $this->assertEquals('invert_alpha_numeric', vr()->not()->alpha_numeric());
    }

    public function testCanValidateAlphaWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->alpha_numeric();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateAlphaNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->not()->alpha_numeric();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->alpha_numeric();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->not()->alpha_numeric();

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return ['test' => '123azÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝÑàáâãäåçèéêëìíîïðòóôõöùúûüýÿñ'];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return ['test' => 'hello *(^*^*&\')'];
    }
}
