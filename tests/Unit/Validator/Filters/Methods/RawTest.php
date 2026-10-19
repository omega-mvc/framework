<?php

declare(strict_types=1);

namespace Tests\Validator\Filters\Methods;

use Omega\Validator\Validator;
use Tests\TestCase;

final class RawTest extends TestCase
{
    public function testCanAddFilterRuleUsingRaw(): void
    {
        $validation = new Validator([
            'test' => 'test',
        ]);

        $validation->filter('test')->raw('upper_case');

        $this->assertEquals('TEST', $validation->filters->get('test'));
    }

    public function testCanAddFilterRuleUsingRawCombineWithOther(): void
    {
        $validation = new Validator([
            'test' => ' test ',
        ]);

        $validation->filter('test')->raw('upper_case')->trim();

        $this->assertEquals('TEST', $validation->filters->get('test'));
    }
}
