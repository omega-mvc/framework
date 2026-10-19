<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Rule\ValidPool;
use Omega\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Validator::class)]
final class RunValidationTest extends TestCase
{
    public function testCanRunValidationUsingMethodIsValid(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->test->required();

        $this->assertTrue($valid->isValid());
    }

    public function testCanRunValidationUsingMethodPassed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $valid                     = new Validator(['test' => 'test']);

        $valid->test->required();

        $this->assertTrue($valid->isValid());
    }

    public function testValidationFalseBecauseMethodPassRequiredValidValidation(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $passed = Validator::make()->validation(fn (ValidPool $v): array => [
            $v('test')->required(),
        ])->passed();

        $this->assertFalse($passed);
    }

    public function testValidationFalseBecauseMethodPassRequiredSubmittedForm(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertFalse(Validator::make()->passed());
    }

    public function testValidationFalseBecauseMethodPassRequiredSubmittedFormAndValidValidation(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $passed = Validator::make()->validation(fn (ValidPool $v): array => [
            $v('test')->required(),
        ])->passed();

        $this->assertFalse($passed);
    }

    public function testCanRunValidationUsingMethodIsValidWithClosureParam(): void
    {
        $valid = new Validator([
            'test1' => 'test',
            'test2' => 'test',
            'test3' => 'test',
            'test4' => 'test',
            'test5' => 'test',
            'test6' => 'test',
            'test7' => 'test',
        ]);

        $result = $valid->isValid(
            fn (ValidPool $pool): array => [
                $pool->rule('test1')->required(),
                $pool('test2')->required(),
                $pool->test3->required(),
                $pool->rule('test4', 'test5')->required(),
                $pool('test6', 'test7')->required(),
            ]
        );

        $this->assertTrue($result);
    }

    public function testCanRunValidationUsingMethodIsValidWithClosureReturn(): void
    {
        $valid = new Validator([
            'test1' => 'test',
            'test2' => 'test',
            'test3' => 'test',
            'test4' => 'test',
            'test5' => 'test',
        ]);

        $result = $valid->isValid(function (): ValidPool {
            $pool = new ValidPool();
            $pool->rule('test1')->required();
            $pool('test2')->required();
            $pool->test3->required();
            $pool->rule('test4', 'test5')->required();

            return $pool;
        });

        $this->assertTrue($result);
    }

    public function testCanRunValidationUsingMethodIsValidWithClosureParamButNotValid(): void
    {
        $valid = new Validator(['test' => '']);

        $result = $valid->isValid(fn (ValidPool $pool): array => [
            $pool('test')->required(),
        ]);

        $this->assertFalse($result);
    }

    public function testCanRunValidationUsingMethodIfValid(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->test->required();

        $valid->ifValid(function () use ($valid): void {
            $this->assertSame([], $valid->getError());
        })->else(function (array $err): void {
            $this->assertSame([], $err);
        });
    }

    public function testCanRunValidationUsingMethodValidOrException(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->test->required();

        $this->assertTrue($valid->validOrException());
    }

    public function testCanRunValidationUsingMethodValidOrExceptionButNotValid(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->test->required()->min_len(5);

        $this->expectExceptionMessage('vaildate if fallen');
        $valid->validOrException();
    }

    public function testCanRunValidationUsingMethodValidOrError(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->test->required();

        $this->assertTrue($valid->validOrError());
    }

    public function testCanRunValidationUsingMethodValidOrErrorButNotValid(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->test->required()->min_len(5);

        $this->assertIsArray($valid->validOrError());
    }

    public function testMethodIsErrorIsInvertAsMethodIsValid(): void
    {
        $valid = new Validator(['test' => 'test']);

        $valid->test->required();

        $this->assertFalse($valid->isError());
        $this->assertNotEquals($valid->isValid(), $valid->isError());
    }

    public function testMethodFailsIsInvertAsMethodPassed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $valid = new Validator(['test' => 'test']);

        $valid->test->required();

        $this->assertFalse($valid->fails());
        $this->assertNotEquals($valid->passed(), $valid->fails());
    }
}
