<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class SlugTest extends TestCase
{
    public function testCanRanderSlug(): void
    {
        $this->assertEquals('slug', fr()->slug());
    }

    public function testCanFilterSlug(): void
    {
        $fr = new Validator(['field' => 'long title tobe url']);

        $fr->filter('field')->slug();

        $this->assertEquals(['field' => 'long-title-tobe-url'], $fr->filterOut());
    }
}
