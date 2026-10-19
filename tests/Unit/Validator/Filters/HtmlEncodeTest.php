<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class HtmlEncodeTest extends TestCase
{
    public function testCanRanderHtmlencode(): void
    {
        $this->assertEquals('htmlencode', fr()->htmlencode());
    }

    public function testCanFilterHtmlencode(): void
    {
        $fr = new Validator(['field' => '<html>html tag</html>']);

        $fr->filter('field')->htmlencode();

        $this->assertEquals(['field' => '&#60;html&#62;html tag&#60;/html&#62;'], $fr->filterOut());
    }
}
