<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ContainsTest extends TestCase
{
    public function testCanRenderContainsValidation(): void
    {
        $this->assertEquals('contains,one;two', vr()->contains('one', 'two'));
    }

    public function testCanRenderInvertContainsValidation(): void
    {
        $this->assertEquals('invert_contains,one;two', vr()->not()->contains('one', 'two'));
    }

    public function testCanValidateContainsWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test->contains('one', 'two');
        $val->test2->contains('one', 'two', 'with space');

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateContainsNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test->not->contains('one', 'two');
        $val->test2->not->contains('one', 'two', 'with space');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateContainsWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test->contains('one');
        $val->test2->contains('one', 'with spac');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateContainsNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test->not->contains('one');
        $val->test2->not->contains('one', 'with spec');

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return [
            'test1' => 'one',
            'test2' => 'with space',
        ];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return [
            'test1' => 'two',
            'test2' => 'with space',
        ];
    }
}
