<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\vr;

final class StreetAddressTest extends TestCase
{
    /** @var array<string, mixed> */
    private const CORRECT = [
        'test'  => '6 Avondans Road',
        'test2' => 'Calle Mediterráneo 2',
    ];

    /** @var array<string, mixed> */
    private const INCORRECT = [
        'test'  => 'Avondans Road',
        'test2' => 'text',
    ];

    public function testCanRenderStreetAddressValidation(): void
    {
        $this->assertEquals('street_address', vr()->street_address());
    }

    public function testCanRenderInvertStreetAddressValidation(): void
    {
        $this->assertEquals('invert_street_address', vr()->not()->street_address());
    }

    public function testCanValidateStreetAddressWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->street_address();

        $this->assertTrue($val->isValid());
    }

    public function testCanValidateStreetAddressNotWithCorrectInput(): void
    {
        $val = new Validator(self::CORRECT);

        $fieldName = array_keys(self::CORRECT);
        $val->field(...$fieldName)->not->street_address();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateStreetAddressWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->street_address();

        $this->assertFalse($val->isValid());
    }

    public function testCanValidateStreetAddressNotWithIncorrectInput(): void
    {
        $val = new Validator(self::INCORRECT);

        $fieldName = array_keys(self::INCORRECT);
        $val->field(...$fieldName)->not->street_address();

        $this->assertTrue($val->isValid());
    }
}
