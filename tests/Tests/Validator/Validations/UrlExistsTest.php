<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class UrlExistsTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = ['test' => 'https://google.com/'];

    public function testCanRenderUrlExistsValidation(): void
    {
        $this->assertEquals('url_exists', vr()->url_exists());
    }

    public function testCanRenderInvertUrlExistsValidation(): void
    {
        $this->assertEquals('invert_url_exists', vr()->not()->url_exists());
    }

    public function testCanValidateUrlExistsWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->url_exists();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateUrlExistsNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $val->test->not()->url_exists();

        $this->assertFalse($val->isValid());
    }

    // validate with incorrect input field

    // FIXME: url exis validation for incorrect url always return true
}
