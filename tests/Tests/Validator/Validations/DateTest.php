<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class DateTest extends TestCase
{
    public function testCanRenderDateValidation(): void
    {
        $this->assertEquals('date,d/m/Y', vr()->date('d/m/Y'));
    }

    public function testCanRenderInvertDateValidation(): void
    {
        $this->assertEquals('invert_date,d/m/Y', vr()->not()->date('d/m/Y'));
    }

    public function testCanValidateDateWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test1->date('Y/m/d');
        $val->test2->date('d-m-Y H:i');

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateDateNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->test1->not->date('Y/m/d');
        $val->test2->not->date('d-m-Y H:i');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateDateWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test1->date('Y/m/d');
        $val->test2->date('d-m-Y H:i');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateContainsNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->test1->not->date('Y/m/d');
        $val->test2->not->date('d-m-Y H:i');

        $this->assertTrue($val->isValid());
    }

    /** @return array<string, string> */
    private function correctInput(): array
    {
        return [
            'test1' => '2022/11/01',
            'test2' => '31-12-2019 10:10',
        ];
    }

    /** @return array<string, string> */
    private function incorrectInput(): array
    {
        return [
            'test1' => '2022/12/32',
            'test2' => '31-12-2019 10:70',
        ];
    }
}
