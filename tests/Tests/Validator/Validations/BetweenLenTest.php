<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class BetweenLenTest extends TestCase
{
    public function testCanRenderBetweenLenValidation(): void
    {
        $this->assertEquals('between_len,3;11', vr()->between_len(3, 11));
    }

    public function testCanRenderInvertBetweenLenValidation(): void
    {
        $this->assertEquals('invert_between_len,3;11', vr()->not()->between_len(3, 11));
    }

    public function testCanValidateBetweenLenWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->between_len(2, 5);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateBetweenLenNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());
        $val->test->not()->between_len(2, 5);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateBetweenLenWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        // less that & more that
        $val->field('test1', 'test2')->between_len(2, 5);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateBetweenLenNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());
        // less that & more that
        $val->field('test1', 'test2')->not->between_len(2, 5);

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return ['test' => '123'];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return ['test1' => '1', 'test2' => '123456'];
    }
}
