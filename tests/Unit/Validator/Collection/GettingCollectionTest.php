<?php

declare(strict_types=1);

namespace Tests\Validator\Collection;

use Omega\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Collection::class)]
final class GettingCollectionTest extends TestCase
{
    public function testCanGetItemUsingGet(): void
    {
        $collection = new Collection(['key' => 'item']);

        $this->assertEquals('item', $collection->get('key'));
    }

    public function testCanGetItemUsingGetDefaultButNotExist(): void
    {
        /** @var Collection<string, string> $collection */
        $collection = new Collection([]);

        $this->assertEquals('no item', $collection->get('key', 'no item'));
    }

    public function testCanGetItemUsingGetNoDefaultSetButNotExist(): void
    {
        /** @var Collection<string, string> $collection */
        $collection = new Collection([]);

        $this->assertNull($collection->get('key'));
    }

    public function testCanGetItemUsingMagicGet(): void
    {
        $collection = new Collection(['key' => 'item']);

        $this->assertEquals('item', $collection->key);
    }

    public function testCanGetItemUsingMagicGetNoDefaultSetButNotExist(): void
    {
        /** @var Collection<string, string> $collection */
        $collection = new Collection(['seed' => 'value']);

        $this->assertNull($collection->item);
    }
}
