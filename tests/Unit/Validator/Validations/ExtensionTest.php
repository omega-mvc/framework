<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class ExtensionTest extends TestCase
{
    public function testCanRenderExtensionValidation(): void
    {
        $this->assertEquals('extension,png;jpg;gif', vr()->extension('png', 'jpg', 'gif'));
    }

    public function testCanRenderInvertExtensionValidation(): void
    {
        $this->assertEquals('invert_extension,png;jpg;gif', vr()->not->extension('png', 'jpg', 'gif'));
    }

    public function testCanValidateExtensionWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->field('files.test')->extension('png');

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateExtensionNotWithCorrectInput(): void
    {
        $val = new Validator($this->correctInput());

        $val->field('files.test')->not->extension('png');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateExtensionWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->field('files.test')->extension('php');

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateExtensionNotWithIncorrectInput(): void
    {
        $val = new Validator($this->incorrectInput());

        $val->field('files.test')->not->extension('php');

        $this->assertTrue($val->isValid());
    }

    /**
     * @return array{files: array{test: array{name: string, type: string, tmp_name: string, error: int, size: int}}}
     */
    private function correctInput(): array
    {
        return [
            'files' => [
                'test' => [
                    'name'     => 'screenshot.png',
                    'type'     => 'image/png',
                    'tmp_name' => '/tmp/phphjatI9',
                    'error'    => 0,
                    'size'     => 22068,
                ],
            ],
        ];
    }

    /**
     * @return array{files: array{test: array{name: string, type: string, tmp_name: string, error: int, size: int}}}
     */
    private function incorrectInput(): array
    {
        return [
            'files' => [
                'test' => [
                    'name'     => 'screenshot.png',
                    'type'     => 'image/png',
                    'tmp_name' => '/tmp/phphjatI9',
                    'error'    => 4,
                    'size'     => 22068,
                ],
            ],
        ];
    }
}
