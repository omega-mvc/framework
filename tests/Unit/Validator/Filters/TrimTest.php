<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class TrimTest extends TestCase
{
    public function testCanRanderTrim(): void
    {
        $this->assertEquals('trim', fr()->trim());
    }

    public function testCanFilterTrim(): void
    {
        $fr = new Validator(['field' => '  nomore space  ']);

        $fr->filter('field')->trim();

        $this->assertEquals(['field' => 'nomore space'], $fr->filterOut());
    }
}
