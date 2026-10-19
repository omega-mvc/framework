<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class RegexTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = ['test' => 'validation using gump'];

    /** @var array<string, mixed> */
    private const INCORRECT = ['test' => 'testing using pest'];

    public function testCanRenderRegexValidation(): void
    {
        $this->assertEquals('regex,/test-[0-9]{3}/', vr()->regex('/test-[0-9]{3}/'));
    }

    public function testCanRenderInvertRegexValidation(): void
    {
        $this->assertEquals('invert_regex,/test-[0-9]{3}/', vr()->not()->regex('/test-[0-9]{3}/'));
    }

    public function testCanValidateRegexWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->regex('/gump/i');

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateRegexNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->not->regex('/gump/i');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateRegexWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->regex('/gump/i');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateRegexNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $val->test->not()->regex('/gump/i');

        $this->assertTrue($val->isValid());
    }
}
