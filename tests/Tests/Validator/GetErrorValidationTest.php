<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Rule;
use Omega\Validator\Validator;
use Tests\TestCase;

final class GetErrorValidationTest extends TestCase
{
    public function testCanGetErrorMessageWhenValadationIsFallenUsingMethodGetError(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->field('test')->min_len(5);

        $this->assertCount(1, $valid->getError());
    }

    public function testCanGetErrorMessageWhenValadationIsFallenUsingMethodIfValid(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->field('test')->min_len(5);

        $valid->ifValid(function (): void {
        })->else(function (array $err): void {
            $this->assertCount(1, $err);
        });
    }

    public function testCanGetErrorMessageWhenValadationIsFallenUsingMethodValidOrError(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->field('test')->min_len(5);

        $error = $valid->validOrError();

        $this->assertIsArray($error);
        $this->assertCount(1, $error);
    }

    public function testCanGetErrorFromMethodErrors(): void
    {
        Rule::set_error_message('required', '{field} can\'t be null');

        $v = new Validator(['test' => null, 'test2' => 'abc']);

        $v('test')->required();
        $v->filter('test2')->upper_case();

        $errors = $v->errors()->all();

        $this->assertEquals('Test can\'t be null', $errors['test']);
        $this->assertTrue($v->errors->has('test'));
    }

    public function testCanGetErrorFromPropertyErrors(): void
    {
        Rule::set_error_message('required', '{field} can\'t be null');

        $v = new Validator(['test' => null, 'test2' => 'abc']);

        $v('test')->required();
        $v->filter('test2')->upper_case();

        $this->assertTrue($v->errors->has('test'));
        $this->assertEquals('Test can\'t be null', $v->errors->all()['test']);
    }
}
