<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class Guidv4Test extends TestCase
{
    public function testCanRenderGuidv4Validation(): void
    {
        $this->assertEquals('guidv4', vr()->guidv4());
    }

    public function testCanRenderInvertGuidv4Validation(): void
    {
        $this->assertEquals('invert_guidv4', vr()->not->guidv4());
    }

    public function testCanValidateGuidv4WithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test1->guidv4();
        $val->test2->guidv4();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateGuidv4NotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test1->not->guidv4();
        $val->test2->not->guidv4();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateGuidv4WithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test1->guidv4();
        $val->test2->guidv4();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateGuidv4NotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test1->not->guidv4();
        $val->test2->not->guidv4();

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return [
            'test1' => 'A98C5A1E-A742-4808-96FA-6F409E799937',
            'test2' => '7deca41a-3479-4f18-9771-3531f742061b',
        ];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return [
            'test1' => 'A98C5A1EA742480896FA6F409E799937',
            'test2' => '7deca41a-9771-3531f742061b',
        ];
    }
}
