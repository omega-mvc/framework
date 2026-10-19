<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class BooleanTest extends TestCase
{
    public function testCanRanderBoolean(): void
    {
        $this->assertEquals('boolean', fr()->boolean());
    }

    public function testCanFilterBoolean(): void
    {
        $fr = new Validator([
            'field_1' => '1',
            'field_2' => 1,
            'field_3' => 'true',
            'field_4' => 'yes',
            'field_5' => 'on',
        ]);

        $fr->filter('field_1')->boolean();
        $fr->filter('field_2')->boolean();
        $fr->filter('field_3')->boolean();
        $fr->filter('field_4')->boolean();
        $fr->filter('field_5')->boolean();

        $this->assertEquals([
            'field_1' => true,
            'field_2' => true,
            'field_3' => true,
            'field_4' => true,
            'field_5' => true,
        ], $fr->filterOut());
    }
}
