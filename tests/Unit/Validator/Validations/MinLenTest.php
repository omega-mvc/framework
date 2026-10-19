<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class MinLenTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test1' => 'ñándú',
        'test2' => 'ñán',
        'test3' => '',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = [
        'test' => 'ñ',
    ];

    public function testCanRenderMinLenValidation(): void
    {
        $this->assertEquals('min_len,240', vr()->min_len(240));
    }

    public function testCanRenderInvertMinLenValidation(): void
    {
        $this->assertEquals('invert_min_len,240', vr()->not()->min_len(240));
    }

    public function testCanValidateMinLenWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test1->min_len(5);
        $val->test2->min_len(2);
        $val->test3->min_len(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateMinLenNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test1->not->min_len(5);
        $val->test2->not->min_len(2);
        $val->test3->not->min_len(2);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMinLenWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->min_len(2);

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateMinLenNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->not()->min_len(2);

        $this->assertTrue($val->isValid());
    }
}
