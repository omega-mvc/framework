<?php

declare(strict_types=1);

namespace Tests\Validator\Collection;

use Omega\Collection\Collection;
use Tests\TestCase;

final class ConvertCollectionTest extends TestCase
{
    public function testCanConvertToArray(): void
    {
        $array = ['key' => 'item'];

        $this->assertEquals($array, (new Collection($array))->all());
    }
}
