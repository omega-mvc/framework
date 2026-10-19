<?php

declare(strict_types=1);

namespace Tests\Validator\Validations\Methods;

use Omega\Validator\Validator;
use Tests\TestCase;

final class IfMethodTest extends TestCase
{
    public function testCanExecuteMethodUsingIfTrue(): void
    {
        $val = new Validator();

        // output: required
        $val->field('test')->if(fn (): bool => true)->required();

        $this->assertFalse($val->isValid());
    }

    public function testCanExecuteMethodUsingIfFalse(): void
    {
        $val = new Validator();

        // output: required
        $val->field('test')->if(fn (): bool => false)->required();

        $this->assertTrue($val->isValid());
    }

    public function testCanExecuteMethodUsingIfTrueTrue(): void
    {
        $val = new Validator(['test' => '200']);

        // output: numeric|min_len,2
        $val->field('test')->if(fn (): bool => true)->numeric()->if(fn (): bool => true)->min_len(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanExecuteMethodUsingIfTrueFalse(): void
    {
        $val = new Validator(['test' => '1']);

        // output: numeric
        $val->field('test')->if(fn (): bool => true)->numeric()->if(fn (): bool => false)->min_len(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanExecuteMethodUsingIfFalseTrue(): void
    {
        $val = new Validator(['test' => 'test']);

        // output: min_len
        $val->field('test')->if(fn (): bool => false)->numeric()->if(fn (): bool => true)->min_len(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanExecuteMethodUsingIfFalseFalse(): void
    {
        $val = new Validator(['test' => 'text']);

        // output: ''
        $val->field('test')->if(fn (): bool => false)->numeric()->if(fn (): bool => false)->min_len(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanExecuteMethodUsingIfContiuneTrueTrue(): void
    {
        $val = new Validator(['test' => 'test']);

        // output: 'required'
        $val->field('test')->if(fn (): bool => true)->if(fn (): bool => true)->required();

        $this->assertTrue($val->isValid());
    }

    public function testCanExecuteMethodUsingIfContiuneTrueFalseX(): void
    {
        $val = new Validator(['test' => 'text']);

        // output: ''
        $val->field('test')->if(fn (): bool => true)->if(fn (): bool => false)->alpha();

        $this->assertTrue($val->isValid());
    }

    public function testCanExecuteMethodUsingIfContiuneFalseFalse(): void
    {
        $val = new Validator(['test' => 'text']);

        // output: ''
        $val->field('test')->if(fn (): bool => false)->if(fn (): bool => false)->min_len(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanExecuteMethodUsingIfContiuneFalseTrue(): void
    {
        $val = new Validator(['test' => 'text']);

        // output: ''
        $val->field('test')->if(fn (): bool => false)->if(fn (): bool => true)->min_len(2);

        $this->assertTrue($val->isValid());
    }

    public function testCanThrowExceptionUsingIfNoReturn(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Condition closure not return boolean');

        $val = new Validator(['test' => 'test']);

        $val->field('test')->if(function (): void {
            // no return
        });
    }

    public function testCanThrowExceptionUsingIfNoBoolean(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Condition closure not return boolean');

        $val = new Validator(['test' => 'test']);

        $val->field('test')->if(fn (): string => 'test');
    }

    public function testCanValidateCombineWithSubmittedMethod(): void
    {
        $previous                  = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $val                       = new Validator();

        try {
            // output: required
            $val->field('test')->if(fn (): bool => $val->submitted())->required();

            $this->assertFalse($val->isValid());
        } finally {
            if ($previous === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $previous;
            }
        }
    }
}
