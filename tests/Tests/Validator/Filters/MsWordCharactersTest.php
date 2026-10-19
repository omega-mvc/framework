<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Validator;
use Tests\TestCase;

use function Omega\Validator\fr;

final class MsWordCharactersTest extends TestCase
{
    public function testCanRanderMsWordCharacters(): void
    {
        $this->assertEquals('ms_word_characters', fr()->ms_word_characters());
    }

    public function testCanFilterMsWordCharacters(): void
    {
        $fr = new Validator(['field' => '“test”,‘test’,–,…']);

        $fr->filter('field')->ms_word_characters();

        $this->assertEquals(['field' => '"test",\'test\',-,...'], $fr->filterOut());
    }
}
