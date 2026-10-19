<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class BasicTagsTest extends TestCase
{
    public function testCanRanderBasicTags(): void
    {
        $this->assertEquals('basic_tags', fr()->basic_tags());
    }

    public function testCanFilterBasicTags(): void
    {
        $fr = new Validator(['field' => '<script>link</script>']);

        $fr->filter('field')->basic_tags();

        $this->assertEquals(['field' => 'link'], $fr->filterOut());
    }
}
