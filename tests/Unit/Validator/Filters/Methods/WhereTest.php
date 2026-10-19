<?php

declare(strict_types=1);

namespace Tests\Validator\Filters\Methods;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class WhereTest extends TestCase
{
    public function testCanRenderFilerRuleUsingMethodWhereTrue(): void
    {
        $this->assertEquals('trim', fr()->trim()->where(fn () => true));
    }

    public function testCanRenderFilterRuleUsingMethodWhereFalse(): void
    {
        $this->assertEquals('', fr()->trim()->where(fn () => false));
    }

    public function testCanResetFilterRuleUsingMethodWhereTrue(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->trim()->where(fn () => true);

        $this->assertEquals(['test' => 'trim'], $val->filterOut());
    }

    public function testCanResetFilterRuleUsingMethodWhereFalse(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->trim()->where(fn () => false);

        $this->assertEquals(['test' => ' trim '], $val->filterOut());
    }

    public function testCanResetFilterRuleUsingMethodWhereNoReturn(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Condition closure not return boolean');

        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->trim()->where(function () {
            // no return
        });
    }

    public function testCanResetFilterRuleUsingMethodWhereNoBoolean(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Condition closure not return boolean');

        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->trim()->where(fn () => 'test');
    }

    public function testCanExecuteRuleCombineWithSubmittedMethod(): void
    {
        $val = new Validator(['test' => ' trim ']);

        $val->filter('test')->trim()->where(fn () => $val->submitted());

        $this->assertEquals(['test' => ' trim '], $val->filterOut());
    }
}
