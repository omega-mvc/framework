<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class SanitizeNumbersTest extends TestCase
{
    public function testCanRanderSanitizeNumbers(): void
    {
        $this->assertEquals('sanitize_numbers', fr()->sanitize_numbers());
    }

    public function testCanFilterSanitizeNumbers(): void
    {
        $fr = new Validator(['field' => '5-2+3pp']);

        $fr->filter('field')->sanitize_numbers();

        $this->assertEquals(['field' => '5-2+3'], $fr->filterOut());
    }
}
