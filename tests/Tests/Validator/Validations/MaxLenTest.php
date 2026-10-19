<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class MaxLenTest extends TestCase
{
    public function testCanRenderMaxLenValidation(): void
    {
        $this->assertEquals('max_len,240', vr()->max_len(240));
    }

    public function testCanRenderInvertMaxLenValidation(): void
    {
        $this->assertEquals('invert_max_len,240', vr()->not()->max_len(240));
    }

    public function testCanValidateMaxLenWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test1->max_len(5);
        $val->test2->max_len(2);
        $val->test3->max_len(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateMaxLenNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test1->not->max_len(5);
        $val->test2->not->max_len(2);
        $val->test3->not->max_len(2);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMaxLenWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test->max_len(2);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMaxLenNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test->not->max_len(2);

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return [
            'test1' => 'ñándú',
            'test2' => 'ñ',
            'test3' => '',
        ];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return [
            'test' => 'ñán',
        ];
    }
}
