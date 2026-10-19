<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ContainsListTest extends TestCase
{
    public function testCanRenderContainsListValidation(): void
    {
        $this->assertEquals('contains_list,one;two', vr()->contains_list('one', 'two'));
    }

    public function testCanRenderInvertContainsListValidation(): void
    {
        $this->assertEquals('invert_contains_list,one;two', vr()->not()->contains_list('one', 'two'));
    }

    public function testCanValidateContainsListWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->field('test1', 'test2', 'test3')->contains_list('one', 'two', 'with space');

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateContainsListWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->field('test')->contains_list('one', 'two');

        $this->assertFalse($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return [
            'test1' => 'one',
            'test2' => 'with space',
            'test3' => '',
        ];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return [
            'test' => 'three',
        ];
    }
}
