<?php

declare(strict_types=1);

namespace Tests\Validator\Filters\Methods;

use Omega\Validator\Rule\Filter;
use Tests\TestCase;

final class CombineTest extends TestCase
{
    public function testCanCombineFilterClassWithOtherFilterClass(): void
    {
        $filter = new Filter();
        $filter->trim();

        $filter2 = new Filter();
        $filter2->lower_case();
        $filter2->combine($filter);

        $this->assertEquals('lower_case|trim', $filter2->getFilter());
    }
}
