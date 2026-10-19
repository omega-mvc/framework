<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Rule\ValidPool;
use Omega\Validator\Validator;
use Tests\TestCase;

final class AddValidRuleValidationTest extends TestCase
{
    public function testCanAddValidationUsingMethodField(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->field('test')->required();

        $this->assertTrue($valid->isValid());
    }

    public function testCanAddValidationUsingGet(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->test->required();

        $this->assertTrue($valid->isValid());
    }

    public function testCanAddValidationUsingInvoke(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid('test')->required();

        $this->assertTrue($valid->isValid());
    }

    public function testCanAddValidationUsingSet(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->test = 'required';

        $this->assertTrue($valid->isValid());
    }

    public function testCanAddValidatorRuleUsingMethodValidationParam(): void
    {
        $v = Validator::make(['test' => 'test'])
            ->validation(function (): ValidPool {
                $v = new ValidPool();
                $v('test')->required();

                return $v;
            })->isValid();

        $this->assertTrue($v);
    }

    public function testCanAddValidatorRuleUsingMethodValidationReturn(): void
    {
        $v = Validator::make(['test' => 'test'])
            ->validation(fn (ValidPool $v): array => [
                $v('test')->required(),
            ])->isValid();

        $this->assertTrue($v);
    }

    public function testCanAddValidatorRuleUsingPoolsCallbackFromMethodMakeParam(): void
    {
        $v = Validator::make(['test' => 123], fn (ValidPool $v): array => [
            $v('test')->required(),
            $v('d')->alpha(),
        ])->isValid();

        $this->assertTrue($v);
    }

    public function testCanAddValidatorRuleUsingPoolsCallbackFromMethodMakeReturn(): void
    {
        $v = Validator::make(['test' => 123], function (): ValidPool {
            $v = new ValidPool();
            $v('test')->required();
            $v('d')->alpha();

            return $v;
        })->isValid();

        $this->assertTrue($v);
    }

    public function testCanAddNewValidRuleWithExistField(): void
    {
        $validation = new Validator(['test' => 'test']);

        $validation->field('test')->max_len(4);
        $validation->field('test')->required();

        $this->assertTrue($validation->isValid());
    }

    public function testCanAddNewValidRuleWithExistFieldUsingValidpool(): void
    {
        $validation = new Validator(['test' => 'test']);
        $validation->validation(fn (ValidPool $valid): array => [
            $valid('test')->max_len(4),
            $valid('test')->required(),
        ]);

        $this->assertTrue($validation->isValid());
    }

    public function testCanAddMultyFieldUsingMethodField(): void
    {
        $valid = new Validator(['test' => 'test', 'test2' => 'test']);

        $valid->field('test', 'test2')->required();

        $this->assertTrue($valid->isValid());
    }

    public function testCanAddMultyFieldUsingMethodFieldWithFieldExist(): void
    {
        $valid = new Validator(['test' => 'test', 'test2' => 'test2']);

        $valid->field('test', 'test2')->required();
        $valid->field('test')->max_len(4);
        $valid->field('test2')->max_len(5);
        $valid->field('test', 'test2')->min_len(4);

        $this->assertTrue($valid->isValid());
    }

    public function testCanAddMultyFieldUsingMethodInvoke(): void
    {
        $valid = new Validator(['test' => 'test', 'test2' => 'test']);

        $valid('test', 'test2')->required();

        $this->assertTrue($valid->isValid());
    }

    public function testCanAddMultyFieldUsingMethodInvokeWithFieldExist(): void
    {
        $valid = new Validator(['test' => 'test', 'test2' => 'test2']);

        $valid('test', 'test2')->required();
        $valid->field('test')->max_len(4);
        $valid->field('test2')->max_len(5);
        $valid('test', 'test2')->min_len(4);

        $this->assertTrue($valid->isValid());
    }
}
