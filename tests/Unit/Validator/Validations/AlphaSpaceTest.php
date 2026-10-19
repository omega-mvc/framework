<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class AlphaSpaceTest extends TestCase
{
    public function testCanRenderAlphaSpaceValidation(): void
    {
        $this->assertEquals('alpha_space', vr()->alpha_space());
    }

    public function testCanRenderInvertAlphaSpaceValidation(): void
    {
        $this->assertEquals('invert_alpha_space', vr()->not()->alpha_space());
    }

    public function testCanValidateAlphaSpaceWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->alpha_space();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateAlphaSpaceNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->not()->alpha_space();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaSpaceWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->alpha_space();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateAlphaSpaceNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        $val->test->not()->alpha_space();

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return ['test' => ' azÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ '];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return ['test' => '1 azÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ'];
    }
}
