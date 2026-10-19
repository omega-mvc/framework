<?php

declare(strict_types=1);

namespace Tests\Validator\Filters\Methods;

use Omega\Validator\Rule\Filter;
use Tests\TestCase;

final class WithTest extends TestCase
{
    public function testCanCreateFilterUsingWithMethod(): void
    {
        $filter = Filter::with()->trim();

        $this->assertEquals('trim', $filter->getFilter());
    }

    public function testCanCreateFilterUsingWithMethodFromInstance(): void
    {
        $filter = (new Filter())->with()->trim();

        $this->assertEquals('trim', $filter->getFilter());
    }
}
