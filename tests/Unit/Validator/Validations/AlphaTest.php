<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class AlphaTest extends TestCase
{
    public function testCanRenderAlphaValidation(): void
    {
        $this->assertEquals('alpha', vr()->alpha());
    }

    public function testCanRenderInvertAlphaValidation(): void
    {
        $this->assertEquals('invert_alpha', vr()->not()->alpha());
    }

    public function testCanValidateAlphaWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->alpha();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateAlphaNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->not()->alpha();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->alpha();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->not()->alpha();

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return ['test' => 'azÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝÑàáâãäåçèéêëìíîïðòóôõöùúûüýÿñ'];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return ['test' => '123azÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝÑàáâãäåçèéêëìíîïðòóôõöùúûüýÿñ'];
    }
}
