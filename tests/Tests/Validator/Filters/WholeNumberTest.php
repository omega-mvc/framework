<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class WholeNumberTest extends TestCase
{
    public function testCanRanderWholeNumber(): void
    {
        $this->assertEquals('whole_number', fr()->whole_number());
    }

    public function testCanFilterWholeNumber(): void
    {
        $fr = new Validator(['field' => '123']);

        $fr->filter('field')->whole_number();

        $this->assertEquals(['field' => 123], $fr->filterOut());
    }
}
