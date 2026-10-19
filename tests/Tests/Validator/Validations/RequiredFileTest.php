<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class RequiredFileTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
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

    /** @var array<string, mixed> */
    private const INCORRECT = [
        'files' => [],
    ];

    public function testCanRenderRequiredFileValidation(): void
    {
        $this->assertEquals('required_file', vr()->required_file());
    }

    public function testCanRenderInvertRequiredFileValidation(): void
    {
        $this->assertEquals('invert_required_file', vr()->not()->required_file());
    }

    public function testCanValidateRequiredFileWithCorrectInput(): void
    {
        $this->assertTrue($this->validTest(self::CORRECT));
    }

    public function testCanValidateRequiredFileNotWithCorrectInput(): void
    {
        $this->assertFalse($this->validTest(self::CORRECT, true));
    }

    public function testCanValidateRequiredFileWithIncorrectInput(): void
    {
        $this->assertFalse($this->validTest(self::INCORRECT));
    }

    public function testCanValidateRequiredFileNotWithIncorrectInput(): void
    {
        $this->assertTrue($this->validTest(self::INCORRECT, true));
    }

    /**
     * @param array<string, mixed> $input
     */
    private function validTest(array $input, bool $useNot = false): bool
    {
        $val = new Validator($input);

        if ($useNot === false) {
            $val->field('files.test')->required_file();

            return $val->isValid();
        }

        // use not
        $val->field('files.test')->not()->required();

        return $val->isValid();
    }
}
