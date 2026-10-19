<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class SanitizeFloatsTest extends TestCase
{
    public function testCanRanderSanitizeFloats(): void
    {
        $this->assertEquals('sanitize_floats', fr()->sanitize_floats());
    }

    public function testCanFilterSanitizeFloats(): void
    {
        $fr = new Validator(['field' => '12.3']);

        $fr->filter('field')->sanitize_floats();

        $this->assertEquals(['field' => 12.3], $fr->filterOut());
    }
}
