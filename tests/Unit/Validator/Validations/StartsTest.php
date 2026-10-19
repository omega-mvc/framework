<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class StartsTest extends TestCase
{
    public function testCanRenderStartsValidation(): void
    {
        $this->assertEquals('starts,Z', vr()->starts('Z'));
    }

    public function testCanRenderInvertStartsValidation(): void
    {
        $this->assertEquals('invert_starts,Z', vr()->not()->starts('Z'));
    }

    public function testCanValidateRegexWithCorrectInput(): void
    {
        $val = new Validator(['test' => 'test']);

        $val->test->starts('tes');

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateRequiredNotWithCorrectInput(): void
    {
        $val = new Validator(['test' => 'test']);

        $val->test->not->starts('tes');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateRequiredWithIncorrectInput(): void
    {
        $val = new Validator(['test' => 'ttest']);

        $val->test->starts('tes');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateRegexNotWithIncorrectInput(): void
    {
        $val = new Validator(['test' => 'ttest']);

        $val->test->not->starts('tes');

        $this->assertTrue($val->isValid());
    }
}
