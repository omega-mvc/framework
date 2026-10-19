<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class UrlEncodeTest extends TestCase
{
    public function testCanRanderUrlencode(): void
    {
        $this->assertEquals('urlencode', fr()->urlencode());
    }

    public function testCanFilterUrlencode(): void
    {
        $fr = new Validator(['field' => 'test.com/true/right?one=1#2']);

        $fr->filter('field')->urlencode();

        $this->assertEquals(['field' => 'test.com%2Ftrue%2Fright%3Fone%3D1%232'], $fr->filterOut());
    }
}
