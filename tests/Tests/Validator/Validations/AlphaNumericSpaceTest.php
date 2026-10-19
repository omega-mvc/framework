<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class AlphaNumericSpaceTest extends TestCase
{
    public function testCanRenderAlphaNumericSpaceValidation(): void
    {
        $this->assertEquals('alpha_numeric_space', vr()->alpha_numeric_space());
    }

    public function testCanRenderInvertAlphaNumericSpaceValidation(): void
    {
        $this->assertEquals('invert_alpha_numeric_space', vr()->not->alpha_numeric_space());
    }

    public function testCanValidateAlphaWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->alpha_numeric_space();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateAlphaNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->not()->alpha_numeric_space();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->alpha_numeric_space();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->not()->alpha_numeric_space();

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return ['test' => 'azÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝÑàáâãäåçèéêëìíîïðòóôõöùúûüýÿñ123 '];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return ['test' => 'azÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝÑàáâãäåçèéêëìíîïðòóôõöùúûüýÿñ123 -'];
    }
}
