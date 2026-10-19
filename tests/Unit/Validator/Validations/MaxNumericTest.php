<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class MaxNumericTest extends TestCase
{
    public function testCanRenderMaxNumericValidation(): void
    {
        $this->assertEquals('max_numeric,50', vr()->max_numeric(50));
    }

    public function testCanRenderInvertMaxNumericValidation(): void
    {
        $this->assertEquals('invert_max_numeric,50', vr()->not()->max_numeric(50));
    }

    public function testCanValidateMaxNumericWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->field('test1', 'test2', 'test3')->max_numeric(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateMaxNumericNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->field('test1', 'test2', 'test3')->not->max_numeric(2);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMaxNumericWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        // more
        $val->test->max_numeric(2);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMaxNumericNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        // more
        $val->test->not->max_numeric(2);

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, int|string> */
    private function correctInput(): array
    {
        return [
            'test1' => 2,
            'test2' => 1,
            'test3' => '',
        ];
    }

    /** @return array<string, int> */
    private function incorrectInput(): array
    {
        return [
            'test' => 3,
        ];
    }
}
