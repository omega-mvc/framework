<?php

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\Collection;
use Omega\Collection\CollectionImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\TestCase;

use function Omega\Collection\collection;
use function Omega\Collection\collection_immutable;

#[CoversClass(Collection::class)]
final class HelperFunctionTest extends TestCase
{
    public function testCreatesACollectionInstanceWithTheCollectionHelper(): void
    {
        $this->assertSame(Collection::class, collection()::class);
    }

    public function testCollectionHelperWrapsTheGivenArray(): void
    {
        $this->assertSame(['a' => 1], collection(['a' => 1])->all());
    }

    public function testCollectionHelperIsChainable(): void
    {
        $this->assertSame([2, 4, 6], collection([1, 2, 3])->map(static fn (int $item): int => $item * 2)->all());
    }

    public function testCreatesAnImmutableCollectionInstanceWithTheCollectionImmutableHelper(): void
    {
        $this->assertSame(CollectionImmutable::class, collection_immutable()::class);
    }

    public function testCollectionImmutableHelperWrapsTheGivenArray(): void
    {
        $this->assertSame([1, 2], collection_immutable([1, 2])->all());
    }

    public function testCollectionImmutableHelperIsFluent(): void
    {
        $collection = collection_immutable([1, 2, 3]);
        $doubled    = [];

        $result = $collection->each(static function (int $item) use (&$doubled): void {
            $doubled[] = $item * 2;
        });

        $this->assertSame($collection, $result);
        $this->assertSame([2, 4, 6], $doubled);
    }
}
