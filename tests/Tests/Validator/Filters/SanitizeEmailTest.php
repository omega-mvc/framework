<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class SanitizeEmailTest extends TestCase
{
    public function testCanRanderSanitizeEmail(): void
    {
        $this->assertEquals('sanitize_email', fr()->sanitize_email());
    }

    public function testCanFilterSanitizeEmail(): void
    {
        $fr = new Validator(['field' => 'john(.doe)@exa//mple.com']);

        $fr->filter('field')->sanitize_email();

        $this->assertEquals(['field' => 'john.doe@example.com'], $fr->filterOut());
    }
}
