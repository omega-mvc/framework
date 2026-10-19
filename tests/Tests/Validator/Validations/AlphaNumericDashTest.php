<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class AlphaNumericDashTest extends TestCase
{
    public function testCanRenderAlphaNumericDashValidation(): void
    {
        $this->assertEquals('alpha_numeric_dash', vr()->alpha_numeric_dash());
    }

    public function testCanRenderInvertAlphaNumericDashValidation(): void
    {
        $this->assertEquals('invert_alpha_numeric_dash', vr()->not()->alpha_numeric_dash());
    }

    public function testCanValidateAlphaWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->alpha_numeric_dash();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateAlphaNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->not()->alpha_numeric_dash();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->alpha_numeric_dash();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->not()->alpha_numeric_dash();

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return ['test' => 'azÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝÑàáâãäåçèéêëìíîïðòóôõöùúûüýÿñ123-_'];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return ['test' => 'azÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝÑàáâãäåçèéêëìíîïðòóôõöùúûüýÿñ123-_ '];
    }
}
