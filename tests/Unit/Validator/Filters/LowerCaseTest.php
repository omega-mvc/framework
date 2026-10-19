<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class LowerCaseTest extends TestCase
{
    public function testCanRanderLowerCase(): void
    {
        $this->assertEquals('lower_case', fr()->lower_case());
    }

    public function testCanFilterLowerCase(): void
    {
        $fr = new Validator(['field' => 'TEST']);

        $fr->filter('field')->lower_case();

        $this->assertEquals(['field' => 'test'], $fr->filterOut());
    }
}
