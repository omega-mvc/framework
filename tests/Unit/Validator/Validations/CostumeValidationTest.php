<?php

declare(strict_types=1);

namespace Tests\Validator\Validations;

use Omega\Validator\Validator;
use Tests\TestCase;

final class CostumeValidationTest extends TestCase
{
    public function testCanAddCostumeValidation(): void
    {
        $validation = new Validator(['test' => 2]);

        $validation->field('test')->valid(
            fn (string $field, array $input, array $param, mixed $value): bool => true,
            'This field is not odd number'
        );

        $this->assertTrue($validation->isValid());
    }

    public function testCanAddCostumeValidationNot(): void
    {
        $validation = new Validator(['test' => 2]);

        $validation->field('test')->not->valid(
            fn (string $field, array $input, array $param, mixed $value): bool => true,
            'This field is not odd number'
        );

        $this->assertFalse($validation->isValid());
    }

    public function testCanAddCostumeValidationCombineWithOther(): void
    {
        $validation = new Validator(['test' => 22]);

        $validation->field('test')->valid(
            fn (string $field, array $input, array $param, mixed $value): bool => true,
            'This field is not odd number'
        )->min_len(2);

        $this->assertTrue($validation->isValid());
    }

    public function testCanAddCostumeValidationNotCombineWithOther(): void
    {
        $validation = new Validator(['test' => 2]);

        $validation->field('test')->not->valid(
            fn (string $field, array $input, array $param, mixed $value): bool => true,
            'This field is not odd number'
        )->min_len(2);

        $this->assertFalse($validation->isValid());
    }

    public function testCanAddCostumeMessageValidation(): void
    {
        $validation = new Validator(['test' => 2]);

        $validation->field('test')->valid(
            fn (string $field, array $input, array $param, mixed $value): bool => false,
            'Costume error - {field}'
        );

        $this->assertArrayHasKey('test', $validation->errors->all());
        $this->assertEquals('Costume error - Test', $validation->errors->all()['test']);
    }

    public function testCanAddCostumeMessageValidationNot(): void
    {
        $validation = new Validator(['test' => 2]);

        $validation->field('test')->not->valid(
            fn (string $field, array $input, array $param, mixed $value): bool => true,
            'Costume error - {field}'
        );

        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('test', $validation->errors->all());
        $this->assertEquals('Not, Costume error - Test', $validation->errors->all()['test']);
    }
}
