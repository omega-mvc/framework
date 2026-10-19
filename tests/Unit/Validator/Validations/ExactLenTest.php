<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ExactLenTest extends TestCase
{
    public function testCanRenderExactLenValidation(): void
    {
        $this->assertEquals('exact_len,240', vr()->exact_len(240));
    }

    public function testCanRenderInvertExactLenValidation(): void
    {
        $this->assertEquals('invert_exact_len,240', vr()->not()->exact_len(240));
    }

    public function testCanValidateExactLenWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test->exact_len(5);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateExactLenNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test->not->exact_len(5);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateExactLenWithIncorrectInputLessThat(): void
    {
        $val = new Validator($this->incorrectInput());

        // less that
        $val->test->exact_len(2);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateExactLenWithIncorrectInputMoreThat(): void
    {
        $val = new Validator($this->incorrectInput());

        // more that
        $val->test->exact_len(4);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateExactLenNotWithIncorrectInputLessThat(): void
    {
        $val = new Validator($this->incorrectInput());

        // less that
        $val->test->not->exact_len(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateExactLenNotWithIncorrectInputMoreThat(): void
    {
        $val = new Validator($this->incorrectInput());

        // more that
        $val->test->not->exact_len(4);

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return ['test' => 'ñándú'];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return ['test' => 'ñán'];
    }
}
