<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class UpperCaseTest extends TestCase
{
    public function testCanRanderUpperCase(): void
    {
        $this->assertEquals('upper_case', fr()->upper_case());
    }

    public function testCanFilterUpperCase(): void
    {
        $fr = new Validator(['field' => 'test']);

        $fr->filter('field')->upper_case();

        $this->assertEquals(['field' => 'TEST'], $fr->filterOut());
    }
}
