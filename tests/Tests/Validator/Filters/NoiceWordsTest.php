<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class NoiceWordsTest extends TestCase
{
    public function testCanRanderNoiseWords(): void
    {
        $this->assertEquals('noise_words', fr()->noise_words());
    }

    public function testCanFilterNoiceWords(): void
    {
        $fr = new Validator(['field' => 'word']);

        $fr->filter('field')->noise_words();

        $this->assertEquals(['field' => 'word'], $fr->filterOut());
    }
}
