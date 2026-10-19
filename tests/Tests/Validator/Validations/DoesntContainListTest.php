<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class DoesntContainListTest extends TestCase
{
    public function testCanRenderDoesntContainListValidation(): void
    {
        $this->assertEquals('doesnt_contain_list,one;two', vr()->doesnt_contain_list('one', 'two'));
    }

    public function testCanRenderInvertDoesntContainListValidation(): void
    {
        $this->assertEquals(
            'invert_doesnt_contain_list,one;two',
            vr()->not()->doesnt_contain_list('one', 'two')
        );
    }

    public function testCanValidateDoesntContainListWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->field('test1', 'test2')->doesnt_contain_list('one', 'two');

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateDoesntContainListNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->field('test1', 'test2')->not->doesnt_contain_list('one', 'two');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateDoesntContainListWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->field('test')->doesnt_contain_list('one', 'two');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateDoesntContainListNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->field('test')->not->doesnt_contain_list('one', 'two');

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return [
            'test1' => 'three',
            'test2' => 'four',
        ];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return [
            'test' => 'one',
        ];
    }
}
