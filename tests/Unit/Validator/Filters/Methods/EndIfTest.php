<?php

declare(strict_types=1);

namespace Tests\Validator\Filters\Methods;

use Omega\Validator\Rule\Filter;
use ReflectionProperty;
use Tests\TestCase;

final class EndIfTest extends TestCase
{
    public function testCanRenderEndIfFilter(): void
    {
        $this->assertEquals('trim', (new Filter())->if(fn (): bool => true)->end_if()->trim()->getFilter());
    }

    public function testCanRenderBothIfAndEndIfFilter(): void
    {
        $this->assertEquals('trim', (new Filter())->if(fn (): bool => false)->end_if()->trim()->getFilter());
    }

    public function testReOpensTheFilterRulesBlockAfterAnEndIf(): void
    {
        $this->assertEquals('upper_case', (new Filter())->if(fn (): bool => false)->trim()->end_if()->upper_case()->getFilter());
    }

    public function testCanDirectlyAppendTheEndIfRule(): void
    {
        $this->assertEquals('', (new Filter())->end_if()->getFilter());
    }

    public function testAppendsTheEndIfMarkerToTheFilterRulesArray(): void
    {
        $filter = new Filter();
        $filter->end_if();
        $property = new ReflectionProperty(Filter::class, 'filter_rule');
        $this->assertEquals(['end_if'], $property->getValue($filter));
    }
}
