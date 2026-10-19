<?php

declare(strict_types=1);

namespace Tests\Validator\Filters\Methods;

use Omega\Validator\Validator;
use Tests\TestCase;

final class IfMethodTest extends TestCase
{
    public function testCanExecuteMethodUsingIfTrue(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => true)->trim();

        $this->assertEquals(['test' => 'trim'], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfFalse(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => false)->trim();

        $this->assertEquals(['test' => ' trim '], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfTrueTrue(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => true)->trim()->if(fn () => true)->upper_case();

        $this->assertEquals(['test' => 'TRIM'], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfTrueFalse(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => true)->trim()->if(fn () => false)->upper_case();

        $this->assertEquals(['test' => 'trim'], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfFalseTrue(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => false)->trim()->if(fn () => true)->upper_case();

        $this->assertEquals(['test' => ' TRIM '], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfFalseFalse(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => false)->trim()->if(fn () => false)->upper_case();

        $this->assertEquals(['test' => ' trim '], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfContiuneTrueTrue(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => true)->if(fn () => true)->trim();

        $this->assertEquals(['test' => 'trim'], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfContiuneTrueFalseX(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => true)->if(fn () => false)->trim();

        $this->assertEquals(['test' => ' trim '], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfContiuneFalseFalse(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => false)->if(fn () => false)->trim();

        $this->assertEquals(['test' => ' trim '], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfContiuneFalseTrue(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => false)->if(fn () => true)->trim();

        $this->assertEquals(['test' => 'trim'], $val->filterOut());
    }

    public function testCanExecuteRuleCombineWithSubmittedMethod(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => $val->submitted())->trim();

        $this->assertEquals(['test' => ' trim '], $val->filterOut());
    }

    public function testCanExecuteMethodUsingIfNoReturn(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Condition closure not return boolean');

        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(function () {
            // no return
        })->trim();
    }

    public function testCanExecuteMethodUsingIfNoBoolean(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Condition closure not return boolean');

        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->if(fn () => 'test')->trim();
    }
}
