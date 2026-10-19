<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class SanitizeStringTest extends TestCase
{
    public function testCanRanderSanitizeString(): void
    {
        $this->assertEquals('sanitize_string', fr()->sanitize_string());
    }

    public function testCanFilterSanitizeString(): void
    {
        $fr = new Validator(['field' => '<h1>Hello World!</h1>']);

        $fr->filter('field')->sanitize_string();

        $this->assertEquals(['field' => 'Hello World!'], $fr->filterOut());
    }
}
