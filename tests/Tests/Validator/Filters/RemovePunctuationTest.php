<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class RemovePunctuationTest extends TestCase
{
    public function testCanRanderRmpunctuation(): void
    {
        $this->assertEquals('rmpunctuation', fr()->rmpunctuation());
    }

    public function testCanFilterRmpunctuation(): void
    {
        $fr = new Validator(['field' => 'is true?']);

        $fr->filter('field')->rmpunctuation();

        $this->assertEquals(['field' => 'is true'], $fr->filterOut());
    }
}
