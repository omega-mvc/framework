<?php

declare(strict_types=1);

namespace Tests\Validator\Collection;

use Omega\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Collection::class)]
final class CountingCollectionTest extends TestCase
{
    public function testCountTotalItemsInCollection(): void
    {
        $collection = new Collection([
            'key_1' => 'item_1',
            'key_2' => 'item_2',
            'key_3' => 'item_3',
            'key_4' => 'item_4',
            'key_5' => 'item_5',
        ]);

        $this->assertEquals(5, $collection->count());
    }

    public function testCountTotalItemsInCollectionNull(): void
    {
        $this->assertEquals(0, (new Collection([]))->count());
    }
}
